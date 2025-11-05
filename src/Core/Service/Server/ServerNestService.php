<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

readonly class ServerNestService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
    )
    {
    }

    public function getNestEggs(int $nestId): array
    {
        return $this->pterodactylApplicationService
            ->getApplicationApi()
            ->nestEggs()
            ->all($nestId)
            ->toArray();
    }

    public function getServerAvailableEggs(Server $server): array
    {
        $nestEggs = $this->getNestEggs($server->getServerProduct()->getNest());

        return array_filter($nestEggs, function (array $egg) use ($server) {
            return in_array($egg['id'], $server->getServerProduct()->getEggs());
        });
    }
}
