<?php

namespace App\Core\Service\Pterodactyl;

readonly class PterodactylUsernameService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
    ) {}

    public function generateUsername(string $username): string
    {
        if (str_contains($username, '@')) {
            $username = explode('@', $username)[0];
            $username = explode('+', $username)[0];
        }

        $users = $this->pterodactylApplicationService
            ->getApplicationApi()
            ->users()
            ->getAllUsers(['filter' => ['username' => $username]]);

        if (!$users->isEmpty()) {
            $username = sprintf('%s%d', $username, rand(1, 999));
            return $this->generateUsername($username);
        }

        return $username;
    }
}