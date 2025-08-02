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
        $serverProduct = $server->getServerProduct();
        if (!$serverProduct) {
            return [];
        }
        
        $nestEggs = $this->getNestEggs($serverProduct->getNest());

        return array_filter($nestEggs, function ($egg) use ($serverProduct) {
            return in_array($egg->id, $serverProduct->getEggs());
        });
    }
}
