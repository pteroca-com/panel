<?php

namespace App\Core\Service\Pterodactyl;

class PterodactylUsernameService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
    ) {}

    public function generateUsername(string $username): string
    {
        if (str_contains($username, '@')) {
            $username = explode('@', $username)[0];
            $username = explode('+', $username)[0];
        }

        $user = $this->pterodactylService->getApi()->users->all(['filter' => ['username' => $username]])->toArray();

        if (!empty($user)) {
            $username = sprintf('%s%d', $username, rand(1, 999));
            return $this->generateUsername($username);
        }

        return $username;
    }
}