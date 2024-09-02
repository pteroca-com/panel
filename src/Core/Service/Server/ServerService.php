<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
    ) {}

    public function getServerDetails(Server $server): ?array
    {
        $pterodactylServer = $this->pterodactylService->getApi()->servers->get(
            $server->getPterodactylServerId(),
            ['include' => ['allocations']],
        );
        if (!$pterodactylServer->has('relationships')) {
            return null;
        }
        return [
            'ip' => sprintf(
                '%s:%s',
                $pterodactylServer->get('relationships')['allocations'][0]['ip'],
                $pterodactylServer->get('relationships')['allocations'][0]['port'],
            ),
            'limits' => $pterodactylServer->get('limits'),
            'feature-limits' => $pterodactylServer->get('feature_limits'),
        ];
    }

    public function getServer(int $serverId): ?Server
    {
        return $this->serverRepository->find($serverId);
    }
}
