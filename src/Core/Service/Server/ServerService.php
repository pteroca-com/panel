<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\ServerDetailsDTO;
use App\Core\Entity\Server;
use App\Core\Enum\ServerStateEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

readonly class ServerService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private ServerRepository              $serverRepository,
        private ServerSubuserRepository       $serverSubuserRepository,
    ) {}

    public function getServerStateByClient(
        UserInterface $user,
        Server $server,
    ): ?ServerDetailsDTO
    {
        $clientApi = $this->pterodactylApplicationService
            ->getClientApi($user);

        if (false === $server->getIsSuspended()) {
            $serverState = $clientApi->servers()
                ->getServerResources($server->getPterodactylServerIdentifier())['current_state'] ?? null;
        } else {
            $serverState = ServerStateEnum::SUSPENDED->value;
        }
        
        $serverDetails = $this->getServerDetails($server);

        return new ServerDetailsDTO(
            identifier: $server->getPterodactylServerIdentifier(),
            name: $serverDetails->name,
            description: $serverDetails->description,
            ip: $serverDetails->ip,
            limits: $serverDetails->limits,
            featureLimits: $serverDetails->featureLimits,
            egg: $serverDetails->egg,
            state: ServerStateEnum::tryFrom($serverState),
        );
    }

    public function getServerDetails(Server $server, ?object $pterodactylServer = null): ?ServerDetailsDTO
    {
        if (empty($pterodactylServer)) {
            $pterodactylServer = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->servers()
                ->getServer(
                    $server->getPterodactylServerId(),
                    ['allocations', 'egg'],
                );
        }

        if (!$pterodactylServer->has('relationships')) {
            return null;
        }

        $allocations = $pterodactylServer->get('relationships')['allocations'] ?? null;
        $primaryId = $pterodactylServer->get('allocation') ?? null;
        $primary = null;

        if ($allocations instanceof \App\Core\DTO\Pterodactyl\Collection) {
            foreach ($allocations as $a) {
                if ($a->get('id') === $primaryId) {
                    $primary = $a;
                    break;
                }
            }

            if ($primary === null && !$allocations->isEmpty()) {
                $primary = $allocations->first();
            }
        }

        if ($primary === null) {
            return null;
        }

        $host = $primary->get('alias') ?? $primary->get('ip') ?? null;
        $port = $primary->get('port') ?? null;

        $serverAddress = null;
        if ($host && $port) {
            if (str_contains($host, ':') && $host[0] !== '[') {
                $host = '['.$host.']';
            }
            $serverAddress = $host.':'.$port;
        }

        $eggRelationship = $pterodactylServer->get('relationships')['egg'] ?? null;
        $eggData = $eggRelationship instanceof \App\Core\DTO\Pterodactyl\Resource
            ? $eggRelationship->toArray()
            : [];

        return new ServerDetailsDTO(
            identifier: $server->getPterodactylServerIdentifier(),
            name: $pterodactylServer->get('name'),
            description: $pterodactylServer->get('description'),
            ip: $serverAddress,
            limits: $pterodactylServer->get('limits'),
            featureLimits: $pterodactylServer->get('feature_limits'),
            egg: $eggData,
        );
    }

    public function getServer(string $pterodactylServerIdentifier): ?Server
    {
        return $this->serverRepository
            ->findOneBy(['pterodactylServerIdentifier' => $pterodactylServerIdentifier]);
    }

    public function getServersWithAccess(UserInterface $user): array
    {
        $ownedServers = $this->serverRepository->getActiveServersByUser($user);
        $ownedServerIds = array_map(fn(Server $server) => $server->getId(), $ownedServers);

        $subuserServers = [];
        $subusers = $this->serverSubuserRepository->getSubusersByUser($user);
        foreach ($subusers as $serverSubuser) {
            $server = $serverSubuser->getServer();
            if ($server->getDeletedAt() === null) {
                if (!in_array($server->getId(), $ownedServerIds)) {
                    $subuserServers[] = $server;
                }
            }
        }

        return array_merge($ownedServers, $subuserServers);
    }
}
