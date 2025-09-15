<?php

namespace App\Core\Service\Pterodactyl;

class PterodactylUsernameService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
    ) {}

    public function generateUsername(string $username): string
    {
        if (str_contains($username, '@')) {
            $username = explode('@', $username)[0];
            $username = explode('+', $username)[0];
        }

        $user = $this->pterodactylApplicationService
            ->getApplicationApi()
            ->users()
            ->getAllUsers(['filter' => ['username' => $username]]);
        $user = current($user);

        if (!empty($user)) {
            $username = sprintf('%s%d', $username, rand(1, 999));
            return $this->generateUsername($username);
        }

        return $username;
    }
}