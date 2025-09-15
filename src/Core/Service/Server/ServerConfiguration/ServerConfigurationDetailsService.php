<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
    ) {
        parent::__construct($this->pterodactylApplicationService);
    }

    public function updateServerDetails(Server $server, UserInterface $user, string $serverName, ?string $serverDescription): void
    {
        $pterodactylClientApi = $this->pterodactylApplicationService
            ->getClientApi($user);
        $pterodactylServer = $pterodactylClientApi->servers()
            ->getServer($server->getPterodactylServerIdentifier());
        $description = $serverDescription ?? $pterodactylServer->get('description');

        $pterodactylClientApi
            ->servers()
            ->updateServerName(
                $server->getPterodactylServerIdentifier(),
                $serverName,
                $description
            );
    }
}
