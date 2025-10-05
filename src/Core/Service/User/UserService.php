<?php

namespace App\Core\Service\User;

use App\Core\Contract\UserInterface;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use App\Core\Exception\PterodactylUserNotFoundException;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylAccountService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Pterodactyl\PterodactylUsernameService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PterodactylService $pterodactylService,
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
            $createdUser = $this->pterodactylService->getApi()->users->create([
                'email' => $user->getEmail(),
                'username' => $this->usernameService->generateUsername($user->getEmail()),
                'first_name' => $user->getName(),
                'last_name' => $user->getSurname(),
                'password' => $plainPassword,
            ]);
            $user->setPterodactylUserId($createdUser->id);
        } catch (Exception $exception) {
            $this->logger->error('Failed to create Pterodactyl account during user creation', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }

        try {
            $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
            $user->setPterodactylUserApiKey($pterodactylClientApiKey);
        } catch (CouldNotCreatePterodactylClientApiKeyException|Exception $exception) {
            $this->pterodactylService->getApi()->users->delete($createdUser->id);
            $this->logger->error('Failed to create Pterodactyl client API key during user creation', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }
    }

    public function createOrRestoreUser(UserInterface $user, string $plainPassword): array
    {
        $existingDeletedUser = $this->userRepository->findDeletedByEmail($user->getEmail());
        
        if ($existingDeletedUser) {
            $existingDeletedUser->restore();
            $existingDeletedUser->setName($user->getName());
            $existingDeletedUser->setSurname($user->getSurname());
            $existingDeletedUser->setRoles($user->getRoles());
            $existingDeletedUser->setIsVerified($user->isVerified());
            $existingDeletedUser->setIsBlocked($user->isBlocked());
            $existingDeletedUser->setBalance($user->getBalance());
            
            if ($plainPassword) {
                $existingDeletedUser->setPlainPassword($plainPassword);
                $hashedPassword = $this->passwordHasher->hashPassword($existingDeletedUser, $plainPassword);
                $existingDeletedUser->setPassword($hashedPassword);
            }

            try {
                if ($existingDeletedUser->getPterodactylUserId()) {
                    $this->updateUserInPterodactyl($existingDeletedUser, $plainPassword);
                } else {
                    $this->createUserWithPterodactylAccount($existingDeletedUser, $plainPassword);
                }
            } catch (Exception $exception) {
                $this->logger->error('Failed to restore user in Pterodactyl', [
                    'exception' => $exception,
                    'user' => $existingDeletedUser,
                ]);
                throw new Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
            }

            return ['action' => 'restored', 'user' => $existingDeletedUser];
        } else {
            $this->createUserWithPterodactylAccount($user, $plainPassword);
            return ['action' => 'created', 'user' => $user];
        }
    }

    public function updateUserInPterodactyl(UserInterface $user, ?string $plainPassword = null): void
    {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        try {
            $pterodactylAccount = $this->pterodactylService
                ->getApi()
                ->users
                ->get($user->getPterodactylUserId());
            
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
                
                $this->pterodactylService->getApi()->users->update(
                    $user->getPterodactylUserId(),
                    $pterodactylAccountDetails
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
            $this->pterodactylService->getApi()->users->delete($user->getPterodactylUserId());
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
