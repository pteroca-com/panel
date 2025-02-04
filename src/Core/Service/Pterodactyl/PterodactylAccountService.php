<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Entity\User;
use Exception;
use Timdesm\PterodactylPhpApi\Resources\User as PterodactylUser;

class PterodactylAccountService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylUsernameService $usernameService,
    )
    {
    }

    public function createPterodactylAccount(User $user, string $plainPassword): PterodactylUser
    {
       try {
           return $this->pterodactylService->getApi()->users->create([
               'email' => $user->getEmail(),
               'username' => $this->usernameService->generateUsername($user->getEmail()),
               'first_name' => $user->getName(),
               'last_name' => $user->getSurname(),
               'password' => $plainPassword,
           ]);
       } catch (Exception $e) {
           $deepErrors = $e->errors['errors'] ?? [];
           $errors = array_map(fn($error) => $error['detail'], $deepErrors);
           $error = sprintf(
               'Error during creating Pterodactyl account: %s (%s)',
               $e->getMessage(),
               implode(', ', $errors)
           );

           throw new Exception($error);
       }
    }

    public function updatePterodactylAccountPassword(User $user, string $plainPassword): PterodactylUser
    {
        try {
            $currentPterodactylUser = $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId());
            return $this->pterodactylService->getApi()->users->update($user->getPterodactylUserId(), [
                'email' => $currentPterodactylUser->get('email'),
                'username' => $currentPterodactylUser->get('username'),
                'first_name' => $currentPterodactylUser->get('first_name'),
                'last_name' => $currentPterodactylUser->get('last_name'),
                'password' => $plainPassword,
            ]);
        } catch (Exception $e) {
            throw new Exception(sprintf('Error while updating user password: %s', $e->getMessage()));
        }
    }

    public function deletePterodactylAccount(User $user): void
    {
        $this->pterodactylService->getApi()->users->delete($user->getPterodactylUserId());
    }
}
