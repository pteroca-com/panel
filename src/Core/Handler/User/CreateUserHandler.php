<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\CreateUserCommand;
use App\Core\Entity\User;
use App\Core\Enum\SystemRoleEnum;
use App\Core\Event\Cli\CreateUser\UserCreationProcessCompletedEvent;
use App\Core\Event\Cli\CreateUser\UserCreationProcessFailedEvent;
use App\Core\Event\Cli\CreateUser\UserCreationProcessStartedEvent;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
use App\Core\Service\Security\RoleManager;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly PterodactylAccountService $pterodactylAccountService,
        private readonly PterodactylClientApiKeyService $pterodactylClientApiKeyService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
        private readonly RoleManager $roleManager,
    ) {}

    /**
     * @throws CouldNotCreatePterodactylClientApiKeyException
     */
    public function handle(object $command): mixed
    {
        assert($command instanceof CreateUserCommand);

        $startTime = new DateTimeImmutable();

        if (empty($command->email) || empty($command->password)) {
            $context = $this->eventContextService->buildCliContext('pteroca:user:create', [
                'role' => $command->roleName,
            ]);

            $this->eventDispatcher->dispatch(
                new UserCreationProcessFailedEvent(
                    'User credentials not set',
                    $command->email ?: 'UNKNOWN',
                    $command->roleName,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw new RuntimeException('User credentials not set');
        }

        $context = $this->eventContextService->buildCliContext('pteroca:user:create', [
            'email' => $command->email,
            'role' => $command->roleName,
        ]);

        $this->eventDispatcher->dispatch(
            new UserCreationProcessStartedEvent(
                $startTime,
                $command->email,
                $command->roleName,
                $context
            )
        );

        try {
            $user = (new User())
                ->setEmail($command->email)
                ->setPassword('')
                ->setBalance(0)
                ->setName('Admin')
                ->setSurname('Admin');

            $role = $this->roleManager->getRoleByName($command->roleName);
            if ($role) {
                $user->addUserRole($role);
            } else {
                $defaultRole = $this->roleManager->getRoleByName(SystemRoleEnum::ROLE_USER->value);
                if ($defaultRole) {
                    $user->addUserRole($defaultRole);
                }
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
            $user->setPassword($hashedPassword);

            $hasPterodactylAccount = false;
            $hasApiKey = false;
            $createdWithoutApiKey = false;

            try {
                $pterodactylAccount = $this->pterodactylAccountService->createPterodactylAccount($user, $command->password);
            } catch (Exception $exception) {
                $message = 'Could not create Pterodactyl account: ' . $exception->getMessage();

                $this->eventDispatcher->dispatch(
                    new UserCreationProcessFailedEvent(
                        $message,
                        $command->email,
                        $command->roleName,
                        new DateTimeImmutable(),
                        $context
                    )
                );

                throw new RuntimeException($message);
            }

            if (!empty($pterodactylAccount->id)) {
                $user->setPterodactylUserId($pterodactylAccount->id);
                $hasPterodactylAccount = true;

                try {
                    $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
                    $user->setPterodactylUserApiKey($pterodactylClientApiKey);
                    $hasApiKey = true;
                } catch (CouldNotCreatePterodactylClientApiKeyException $exception) {
                    if (!$command->allowCreateWithoutApiKey) {
                        try {
                            $this->pterodactylAccountService->deletePterodactylAccount($user);
                        } catch (Exception $rollbackException) {
                            $failureMessage = sprintf(
                                'Could not create API key AND rollback failed: %s. Original error: %s',
                                $rollbackException->getMessage(),
                                $exception->getMessage()
                            );

                            $this->eventDispatcher->dispatch(
                                new UserCreationProcessFailedEvent(
                                    $failureMessage,
                                    $command->email,
                                    $command->roleName,
                                    new DateTimeImmutable(),
                                    $context
                                )
                            );

                            throw new RuntimeException($failureMessage);
                        }

                        $this->eventDispatcher->dispatch(
                            new UserCreationProcessFailedEvent(
                                $exception->getMessage(),
                                $command->email,
                                $command->roleName,
                                new DateTimeImmutable(),
                                $context
                            )
                        );

                        throw $exception;
                    }

                    $createdWithoutApiKey = true;
                }
            }

            $this->userRepository->save($user);

            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            $this->eventDispatcher->dispatch(
                new UserCreationProcessCompletedEvent(
                    $user->getId() ?? 0,
                    $command->email,
                    $command->roleName,
                    $hasPterodactylAccount,
                    $hasApiKey,
                    $createdWithoutApiKey,
                    $duration,
                    $endTime,
                    $context
                )
            );
        } catch (RuntimeException|CouldNotCreatePterodactylClientApiKeyException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->eventDispatcher->dispatch(
                new UserCreationProcessFailedEvent(
                    $e->getMessage(),
                    $command->email,
                    $command->roleName,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw $e;
        }

        return null;
    }
}
