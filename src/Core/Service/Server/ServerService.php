<?php

namespace App\Core\Service\Server;

use App\Core\DTO\ServerDetailsDTO;
use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
    ) {}

    public function getServerDetails(Server $server, ?object $pterodactylServer = null): ?ServerDetailsDTO
    {
        if (empty($pterodactylServer)) {
            $pterodactylServer = $this->pterodactylService->getApi()->servers->get(
                $server->getPterodactylServerId(),
                ['include' => ['allocations', 'egg']],
            );
        }

        if (!$pterodactylServer->has('relationships')) {
            return null;
        }

        $serverIpAddress = sprintf(
            '%s:%s',
            $pterodactylServer->get('relationships')['allocations'][0]['ip'],
            $pterodactylServer->get('relationships')['allocations'][0]['port'],
        );

        return new ServerDetailsDTO(
            name: $pterodactylServer->get('name'),
            description: $pterodactylServer->get('description'),
            ip: $serverIpAddress,
            limits: $pterodactylServer->get('limits'),
            featureLimits: $pterodactylServer->get('feature_limits'),
            egg: $pterodactylServer->get('relationships')['egg']->toArray(),
        );
    }

    public function getServer(string $pterodactylServerIdentifier): ?Server
    {
        return $this->serverRepository
            ->findOneBy(['pterodactylServerIdentifier' => $pterodactylServerIdentifier]);
    }
}
