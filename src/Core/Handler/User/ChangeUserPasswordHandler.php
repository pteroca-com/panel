<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\ChangeUserPasswordCommand;
use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessCompletedEvent;
use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessFailedEvent;
use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessStartedEvent;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangeUserPasswordHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly PterodactylAccountService $pterodactylAccountService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof ChangeUserPasswordCommand);

        $startTime = new DateTimeImmutable();

        if (empty($command->email) || empty($command->password)) {
            $context = $this->eventContextService->buildCliContext('pteroca:user:change-password');

            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessFailedEvent(
                    'User credentials not set',
                    $command->email ?: 'UNKNOWN',
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw new RuntimeException('User credentials not set');
        }

        $context = $this->eventContextService->buildCliContext('pteroca:user:change-password', [
            'email' => $command->email,
        ]);

        $this->eventDispatcher->dispatch(
            new PasswordChangeProcessStartedEvent(
                $startTime,
                $command->email,
                $context
            )
        );

        try {
            $user = $this->userRepository->findOneBy(['email' => $command->email]);
            if (empty($user)) {
                $this->eventDispatcher->dispatch(
                    new PasswordChangeProcessFailedEvent(
                        'User not found',
                        $command->email,
                        new DateTimeImmutable(),
                        $context
                    )
                );

                throw new RuntimeException('User not found');
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
            $user->setPassword($hashedPassword);

            try {
                $this->pterodactylAccountService->updatePterodactylAccountPassword($user, $command->password);
            } catch (Exception $exception) {
                $message = 'Failed to change password in Pterodactyl: ' . $exception->getMessage();
                $this->eventDispatcher->dispatch(
                    new PasswordChangeProcessFailedEvent(
                        $message,
                        $command->email,
                        new DateTimeImmutable(),
                        $context
                    )
                );

                throw new RuntimeException($message);
            }

            $this->userRepository->save($user);

            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessCompletedEvent(
                    $user->getId() ?? 0,
                    $command->email,
                    true,
                    $duration,
                    $endTime,
                    $context
                )
            );
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessFailedEvent(
                    $e->getMessage(),
                    $command->email,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw $e;
        }

        return null;
    }
}
