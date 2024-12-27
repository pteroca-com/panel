<?php

namespace App\Core\Service\Server;

use App\Core\Service\Pterodactyl\PterodactylService;

class ServerNestService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
    )
    {
    }

    public function getNestEggs(int $nestId): array
    {
        return $this->pterodactylService
            ->getApi()
            ->nest_eggs
            ->all($nestId)
            ->toArray();
    }
}