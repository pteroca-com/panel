<?php

namespace App\Core\Handler;

use App\Core\Entity\User;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\UserRepository;
use App\Core\Service\Authorization\RegistrationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateNewUserHandler implements HandlerInterface
{
    private string $userEmail;

    private string $userPassword;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly RegistrationService $registrationService,
    ) {}

    public function handle(): void
    {
        if (empty($this->userEmail) || empty($this->userPassword)) {
            throw new \RuntimeException('User credentials not set');
        }

        $user = (new User())
            ->setEmail($this->userEmail)
            ->setPassword('')
            ->setRoles([UserRoleEnum::ROLE_USER->name, UserRoleEnum::ROLE_ADMIN->name])
            ->setBalance(0)
            ->setName('Admin')
            ->setSurname('Admin');
        $hashedPassword = $this->passwordHasher->hashPassword($user, $this->userPassword);
        $user->setPassword($hashedPassword);

        $pterodactylAccount = $this->registrationService->createPterodactylAccount($user, $this->userPassword);
        if (!empty($pterodactylAccount->id)) {
            $user->setPterodactylUserId($pterodactylAccount->id);
        }

        $this->userRepository->save($user);
    }

    public function setUserCredentials(string $email, string $password): void
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
    }
}