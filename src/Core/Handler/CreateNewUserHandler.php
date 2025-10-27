<?php

namespace App\Core\Handler;

use App\Core\Entity\User;
use App\Core\Enum\UserRoleEnum;
use App\Core\Event\Cli\CreateUser\UserCreationProcessCompletedEvent;
use App\Core\Event\Cli\CreateUser\UserCreationProcessFailedEvent;
use App\Core\Event\Cli\CreateUser\UserCreationProcessStartedEvent;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use App\Core\Repository\UserRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Timdesm\PterodactylPhpApi\Exceptions\ValidationException;

class CreateNewUserHandler implements HandlerInterface
{
    private string $userEmail;

    private string $userPassword;

    private UserRoleEnum $userRole;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly PterodactylAccountService $pterodactylAccountService,
        private readonly PterodactylClientApiKeyService $pterodactylClientApiKeyService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
    ) {}

    public function handle(bool $allowToCreateWithNoPterodactylApiKey = false): void
    {
        $startTime = new DateTimeImmutable();

        // Validate credentials first
        if (empty($this->userEmail) || empty($this->userPassword)) {
            $context = $this->eventContextService->buildCliContext('app:create-new-user', [
                'role' => $this->userRole->name ?? 'UNKNOWN',
            ]);

            $this->eventDispatcher->dispatch(
                new UserCreationProcessFailedEvent(
                    'User credentials not set',
                    $this->userEmail ?? 'UNKNOWN',
                    $this->userRole->name ?? 'UNKNOWN',
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw new RuntimeException('User credentials not set');
        }

        $context = $this->eventContextService->buildCliContext('app:create-new-user', [
            'email' => $this->userEmail,
            'role' => $this->userRole->name,
        ]);

        // Emit process started event
        $this->eventDispatcher->dispatch(
            new UserCreationProcessStartedEvent(
                $startTime,
                $this->userEmail,
                $this->userRole->name,
                $context
            )
        );

        try {
            $user = (new User())
                ->setEmail($this->userEmail)
                ->setPassword('')
                ->setRoles([$this->userRole->name])
                ->setBalance(0)
                ->setName('Admin')
                ->setSurname('Admin');

            $hashedPassword = $this->passwordHasher->hashPassword($user, $this->userPassword);
            $user->setPassword($hashedPassword);

            $hasPterodactylAccount = false;
            $hasApiKey = false;
            $createdWithoutApiKey = false;

            try {
                $pterodactylAccount = $this->pterodactylAccountService->createPterodactylAccount($user, $this->userPassword);
            } catch (ValidationException $exception) {
                $errors = $exception->errors()['errors'] ?? [];
                $errors = array_map(fn($error) => $error['detail'], $errors);
                $message = sprintf('%s Errors: %s', $exception->getMessage(), implode(', ', $errors));

                $this->eventDispatcher->dispatch(
                    new UserCreationProcessFailedEvent(
                        $message,
                        $this->userEmail,
                        $this->userRole->name,
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
                    if (!$allowToCreateWithNoPterodactylApiKey) {
                        // Rollback: delete Pterodactyl account
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
                                    $this->userEmail,
                                    $this->userRole->name,
                                    new DateTimeImmutable(),
                                    $context
                                )
                            );

                            throw new RuntimeException($failureMessage);
                        }

                        $this->eventDispatcher->dispatch(
                            new UserCreationProcessFailedEvent(
                                $exception->getMessage(),
                                $this->userEmail,
                                $this->userRole->name,
                                new DateTimeImmutable(),
                                $context
                            )
                        );

                        throw $exception;
                    }

                    // User chose to continue without API key
                    $createdWithoutApiKey = true;
                }
            }

            $this->userRepository->save($user);

            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            // Emit process completed event
            $this->eventDispatcher->dispatch(
                new UserCreationProcessCompletedEvent(
                    $user->getId() ?? 0,
                    $this->userEmail,
                    $this->userRole->name,
                    $hasPterodactylAccount,
                    $hasApiKey,
                    $createdWithoutApiKey,
                    $duration,
                    $endTime,
                    $context
                )
            );
        } catch (RuntimeException $e) {
            // Already emitted FailedEvent, just re-throw
            throw $e;
        } catch (CouldNotCreatePterodactylClientApiKeyException $e) {
            // Already emitted FailedEvent, just re-throw
            throw $e;
        } catch (Exception $e) {
            // Unexpected exception
            $this->eventDispatcher->dispatch(
                new UserCreationProcessFailedEvent(
                    $e->getMessage(),
                    $this->userEmail,
                    $this->userRole->name,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw $e;
        }
    }

    public function setUserCredentials(string $email, string $password, UserRoleEnum $userRole): void
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
        $this->userRole = $userRole;
    }
}
