<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Entity\User;

class PterodactylClientApiKeyService
{
    private const PTERODACTYL_CLIENT_API_KEY_DESCRIPTION = 'PteroCA Client API Key';

    public function __construct(
        private readonly PterodactylService $pterodactylService,
    )
    {
    }

    public function createClientApiKey(User $user): string
    {
        $createdApiKey = $this->pterodactylService
            ->getApi()
            ->users
            ->http
            ->post(sprintf('users/%d/api-keys', $user->getPterodactylUserId()), [
                'description' => self::PTERODACTYL_CLIENT_API_KEY_DESCRIPTION,
            ]);

        return $createdApiKey->get('identifier');
    }
}