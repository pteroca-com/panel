<?php

namespace App\Core\Handler;

use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessCompletedEvent;
use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessFailedEvent;
use App\Core\Event\Cli\ChangePassword\PasswordChangeProcessStartedEvent;
use App\Core\Repository\UserRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangeUserPasswordHandler implements HandlerInterface
{
    private string $userEmail;

    private string $userPassword;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly PterodactylAccountService $pterodactylAccountService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $startTime = new DateTimeImmutable();

        // Validate credentials first
        if (empty($this->userEmail) || empty($this->userPassword)) {
            $context = $this->eventContextService->buildCliContext('app:change-user-password');

            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessFailedEvent(
                    'User credentials not set',
                    $this->userEmail ?? 'UNKNOWN',
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw new RuntimeException('User credentials not set');
        }

        $context = $this->eventContextService->buildCliContext('app:change-user-password', [
            'email' => $this->userEmail,
        ]);

        // Emit process started event
        $this->eventDispatcher->dispatch(
            new PasswordChangeProcessStartedEvent(
                $startTime,
                $this->userEmail,
                $context
            )
        );

        try {
            $user = $this->userRepository->findOneBy(['email' => $this->userEmail]);
            if (empty($user)) {
                $this->eventDispatcher->dispatch(
                    new PasswordChangeProcessFailedEvent(
                        'User not found',
                        $this->userEmail,
                        new DateTimeImmutable(),
                        $context
                    )
                );

                throw new RuntimeException('User not found');
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $this->userPassword);
            $user->setPassword($hashedPassword);

            try {
                $this->pterodactylAccountService->updatePterodactylAccountPassword($user, $this->userPassword);
            } catch (Exception $exception) {
                $message = 'Failed to change password in Pterodactyl: ' . $exception->getMessage();
                $this->eventDispatcher->dispatch(
                    new PasswordChangeProcessFailedEvent(
                        $message,
                        $this->userEmail,
                        new DateTimeImmutable(),
                        $context
                    )
                );

                throw new RuntimeException($message);
            }

            $this->userRepository->save($user);

            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            // Emit process completed event
            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessCompletedEvent(
                    $user->getId() ?? 0,
                    $this->userEmail,
                    true,
                    $duration,
                    $endTime,
                    $context
                )
            );
        } catch (RuntimeException $e) {
            // Already emitted FailedEvent, just re-throw
            throw $e;
        } catch (Exception $e) {
            // Unexpected exception
            $this->eventDispatcher->dispatch(
                new PasswordChangeProcessFailedEvent(
                    $e->getMessage(),
                    $this->userEmail,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw $e;
        }
    }

    public function setUserCredentials(string $email, string $password): void
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
    }
}
