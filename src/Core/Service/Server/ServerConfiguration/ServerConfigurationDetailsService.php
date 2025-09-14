<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylApplicationService $pterodactylApplicationService,
    ) {
        parent::__construct($this->pterodactylApplicationService);
    }

    public function updateServerDetails(Server $server, UserInterface $user, string $serverName, ?string $serverDescription): void
    {
        $pterodactylClientApi = $this->pterodactylClientService->getApi($user);
        $pterodactylServer = $pterodactylClientApi->servers->get($server->getPterodactylServerIdentifier());
        $description = $serverDescription ?? $pterodactylServer->get('description');

        $pterodactylClientApi
            ->servers
            ->http
            ->post(sprintf('servers/%s/settings/rename', $server->getPterodactylServerIdentifier()), [
                'name' => substr($serverName, 0, 255),
                'description' => substr($description ?? '', 0, 255),
            ]);
    }
}
