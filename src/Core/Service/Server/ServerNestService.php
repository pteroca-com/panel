<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
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

    public function getServerAvailableEggs(Server $server): array
    {
        $nestEggs = $this->getNestEggs($server->getServerProduct()->getNest());

        return array_filter($nestEggs, function ($egg) use ($server) {
            return in_array($egg->id, $server->getServerProduct()->getEggs());
        });
    }
}
