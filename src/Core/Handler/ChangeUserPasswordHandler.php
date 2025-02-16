<?php

namespace App\Core\Handler;

use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Timdesm\PterodactylPhpApi\Exceptions\ValidationException;

class ChangeUserPasswordHandler implements HandlerInterface
{
    private string $userEmail;

    private string $userPassword;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly PterodactylAccountService $pterodactylAccountService,
    ) {}

    public function handle(): void
    {
        if (empty($this->userEmail) || empty($this->userPassword)) {
            throw new \RuntimeException('User credentials not set');
        }

        $user = $this->userRepository->findOneBy(['email' => $this->userEmail]);
        if (empty($user)) {
            throw new \RuntimeException('User not found');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $this->userPassword);
        $user->setPassword($hashedPassword);

        try {
            $this->pterodactylAccountService->updatePterodactylAccountPassword($user, $this->userPassword);
        } catch (ValidationException $exception) {
            $errors = $exception->errors()['errors'] ?? [];
            $errors = array_map(fn($error) => $error['detail'], $errors);
            $message = sprintf('%s Errors: %s', $exception->getMessage(), implode(', ', $errors));
            throw new \RuntimeException($message);
        }

        $this->userRepository->save($user);
    }

    public function setUserCredentials(string $email, string $password): void
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
    }
}
