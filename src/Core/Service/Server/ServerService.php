<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\ServerDetailsDTO;
use App\Core\Entity\Server;
use App\Core\Enum\ServerStateEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerRepository $serverRepository,
        private readonly ServerSubuserRepository $serverSubuserRepository,
    ) {}

    public function getServerStateByClient(
        UserInterface $user,
        Server $server,
    ): ?ServerDetailsDTO
    {
        $clientApi = $this->pterodactylClientService->getApi($user);
        $pterodactylClientServerDetails = $clientApi->servers->http->get(
            sprintf('servers/%s/resources', $server->getPterodactylServerIdentifier()),
        );
        $serverDetails = $this->getServerDetails($server);

        return new ServerDetailsDTO(
            identifier: $server->getPterodactylServerIdentifier(),
            name: $serverDetails->name,
            description: $serverDetails->description,
            ip: $serverDetails->ip,
            limits: $serverDetails->limits,
            featureLimits: $serverDetails->featureLimits,
            egg: $serverDetails->egg,
            state: ServerStateEnum::tryFrom($pterodactylClientServerDetails->get('current_state')),
        );
    }

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
            identifier: $server->getPterodactylServerIdentifier(),
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

    public function getServersWithAccess(UserInterface $user): array
    {
        $ownedServers = $this->serverRepository->getActiveServersByUser($user);
        $ownedServerIds = array_map(fn(Server $server) => $server->getId(), $ownedServers);

        $subuserServers = [];
        $subusers = $this->serverSubuserRepository->getSubusersByUser($user);
        foreach ($subusers as $serverSubuser) {
            $server = $serverSubuser->getServer();
            if ($server instanceof Server && $server->getDeletedAt() === null) {
                if (!in_array($server->getId(), $ownedServerIds)) {
                    $subuserServers[] = $server;
                }
            }
        }

        return array_merge($ownedServers, $subuserServers);
    }
}
