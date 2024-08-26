<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

readonly class ServerService
{
    public function __construct(
        private PterodactylService $pterodactylService,
        private ServerRepository $serverRepository,
    ) {}

    public function getServerDetails(Server $server): ?array
    {
        $pterodactylServer = $this->pterodactylService->getApi()->servers->get(
            $server->getPterodactylServerId(),
            ['include' => ['allocations']],
        );
        if (empty($pterodactylServer)) {
            return null;
        }
        return [
            'ip' => sprintf(
                '%s:%s',
                $pterodactylServer->relationships['allocations'][0]->ip,
                $pterodactylServer->relationships['allocations'][0]->port,
            ),
            'limits' => $pterodactylServer->limits,
            'feature-limits' => $pterodactylServer->feature_limits,
        ];
    }

    public function getServer(int $serverId): ?Server
    {
        return $this->serverRepository->find($serverId);
    }
}
