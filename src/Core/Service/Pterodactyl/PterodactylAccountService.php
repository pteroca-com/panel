<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\UserInterface;
use App\Core\Exception\PterodactylAccountEmailAlreadyExists;
use Exception;
use Timdesm\PterodactylPhpApi\Resources\User as PterodactylUser;

class PterodactylAccountService
{
    private const PTERODACTYL_ACCOUNT_EXISTS_ERROR = 'The email has already been taken.';

    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylUsernameService $usernameService,
    )
    {
    }

    public function createPterodactylAccount(UserInterface $user, string $plainPassword): PterodactylUser
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

           if (in_array(self::PTERODACTYL_ACCOUNT_EXISTS_ERROR, $errors)) {
               $error = sprintf(
                   'Error during creating Pterodactyl account: %s',
                   PterodactylAccountEmailAlreadyExists::MESSAGE,
               );
               throw new PterodactylAccountEmailAlreadyExists($error);
           }

           $error = sprintf(
               'Error during creating Pterodactyl account: %s (%s)',
               $e->getMessage(),
               implode(', ', $errors)
           );

           throw new Exception($error);
       }
    }

    public function updatePterodactylAccountPassword(UserInterface $user, string $plainPassword): PterodactylUser
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

    public function deletePterodactylAccount(UserInterface $user): void
    {
        $this->pterodactylService
            ->getApi()
            ->users
            ->delete($user->getPterodactylUserId());
    }
}
