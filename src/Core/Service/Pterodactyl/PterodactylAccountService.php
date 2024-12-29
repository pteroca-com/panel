<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Entity\User;
use Timdesm\PterodactylPhpApi\Resources\User as PterodactylUser;

class PterodactylAccountService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylUsernameService $usernameService,
    )
    {
    }

    public function createPterodactylAccount(User $user, string $plainPassword): ?PterodactylUser
    {
        $createdPterodactylUser = $this->pterodactylService->getApi()->users->create([
            'email' => $user->getEmail(),
            'username' => $this->usernameService->generateUsername($user->getEmail()),
            'first_name' => $user->getName(),
            'last_name' => $user->getSurname(),
            'password' => $plainPassword,
        ]);

        return $createdPterodactylUser ?? null;
    }
}