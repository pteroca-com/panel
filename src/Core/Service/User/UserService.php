<?php

namespace App\Core\Service\User;

use App\Core\Contract\UserInterface;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use App\Core\Exception\PterodactylUserNotFoundException;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
use App\Core\Service\Pterodactyl\PterodactylUsernameService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly PterodactylUsernameService $usernameService,
        private readonly PterodactylAccountService $pterodactylAccountService,
        private readonly PterodactylClientApiKeyService $pterodactylClientApiKeyService,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createUserWithPterodactylAccount(UserInterface $user, string $plainPassword): void
    {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        try {
            $createdUser = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->users()
                ->createUser([
                    'email' => $user->getEmail(),
                    'username' => $this->usernameService->generateUsername($user->getEmail()),
                    'first_name' => $user->getName(),
                    'last_name' => $user->getSurname(),
                    'password' => $plainPassword,
                ]);
            $user->setPterodactylUserId($createdUser->get('id'));

            try {
                $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
                $user->setPterodactylUserApiKey($pterodactylClientApiKey);
            } catch (CouldNotCreatePterodactylClientApiKeyException $exception) {
                $this->pterodactylApplicationService
                    ->getApplicationApi()
                    ->users()
                    ->deleteUser($createdUser->get('id'));
                $this->logger->error('Failed to create Pterodactyl client API key during user creation', [
                    'exception' => $exception,
                    'user' => $user,
                ]);
                throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        } catch (Exception $exception) {
            $this->logger->error('Failed to create Pterodactyl account during user creation', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }
    }

    public function updateUserInPterodactyl(UserInterface $user, ?string $plainPassword = null): void
    {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        try {
            $pterodactylAccount = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->users()
                ->getUser($user->getPterodactylUserId());
            
            if (!empty($pterodactylAccount->username)) {
                $pterodactylAccountDetails = [
                    'username' => $pterodactylAccount->username,
                    'email' => $user->getEmail(),
                    'first_name' => $user->getName(),
                    'last_name' => $user->getSurname(),
                ];
                
                if ($plainPassword) {
                    $pterodactylAccountDetails['password'] = $plainPassword;
                }

                $this->pterodactylApplicationService
                    ->getApplicationApi()
                    ->users()
                    ->updateUser(
                        $user->getPterodactylUserId(),
                        $pterodactylAccountDetails,
                    );
            }
        } catch (Exception $exception) {
            $this->logger->error('Failed to update Pterodactyl account', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }
    }

    public function deleteUserFromPterodactyl(UserInterface $user): void
    {
        try {
            $this->pterodactylApplicationService
                ->getApplicationApi()
                ->users()
                ->deleteUser($user->getPterodactylUserId());
        } catch (Exception $exception) {
            $this->logger->error('Failed to delete Pterodactyl account', [
                'exception' => $exception,
                'user' => $user,
            ]);
            
            if (str_contains($exception->getMessage(), 'The resource you are looking for could not be found')) {
                throw new PterodactylUserNotFoundException('User not found in Pterodactyl', 0, $exception);
            }
            
            throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }
    }
}
