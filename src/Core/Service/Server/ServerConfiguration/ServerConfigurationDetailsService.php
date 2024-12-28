<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylService $pterodactylService,
    ) {
        parent::__construct($this->pterodactylService);
    }

    public function updateServerDetails(Server $server, string $serverName, string $serverDescription): void
    {
        $this->pterodactylClientService
            ->getApi($server->getUser())
            ->servers
            ->http
            ->post(sprintf('servers/%s/settings/rename', $server->getPterodactylServerIdentifier()), [
                'name' => substr($serverName, 0, 255),
                'description' => substr($serverDescription, 0, 255),
            ]);
    }
}
