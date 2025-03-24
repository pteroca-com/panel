<?php

namespace App\Core\Handler;

use App\Core\Entity\User;
use App\Core\Enum\UserRoleEnum;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
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
    ) {}

    public function handle(bool $allowToCreateWithNoPterodactylApiKey = false): void
    {
        if (empty($this->userEmail) || empty($this->userPassword)) {
            throw new \RuntimeException('User credentials not set');
        }

        $user = (new User())
            ->setEmail($this->userEmail)
            ->setPassword('')
            ->setRoles([$this->userRole->name])
            ->setBalance(0)
            ->setName('Admin')
            ->setSurname('Admin');

        $hashedPassword = $this->passwordHasher->hashPassword($user, $this->userPassword);
        $user->setPassword($hashedPassword);

        try {
            $pterodactylAccount = $this->pterodactylAccountService->createPterodactylAccount($user, $this->userPassword);
        } catch (ValidationException $exception) {
            $errors = $exception->errors()['errors'] ?? [];
            $errors = array_map(fn($error) => $error['detail'], $errors);
            $message = sprintf('%s Errors: %s', $exception->getMessage(), implode(', ', $errors));
            throw new \RuntimeException($message);
        }

        if (!empty($pterodactylAccount->id)) {
            $user->setPterodactylUserId($pterodactylAccount->id);

            try {
                $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
                $user->setPterodactylUserApiKey($pterodactylClientApiKey);
            } catch (CouldNotCreatePterodactylClientApiKeyException $exception) {
                if (!$allowToCreateWithNoPterodactylApiKey) {
                    $this->pterodactylAccountService->deletePterodactylAccount($user);
                    throw $exception;
                }
            }
        }

        $this->userRepository->save($user);
    }

    public function setUserCredentials(string $email, string $password, UserRoleEnum $userRole): void
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
        $this->userRole = $userRole;
    }
}
