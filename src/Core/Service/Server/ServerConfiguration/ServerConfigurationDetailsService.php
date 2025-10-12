<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
    ) {
        parent::__construct($this->pterodactylService);
    }

    public function updateServerDetails(Server $server, UserInterface $user, string $serverName, ?string $serverDescription): void
    {
        $pterodactylClientApi = $this->pterodactylClientService->getApi($user);
        $pterodactylServer = $pterodactylClientApi->servers->get($server->getPterodactylServerIdentifier());
        $description = $serverDescription ?? $pterodactylServer->get('description');
        $preparedServerName = trim(substr($serverName, 0, 255));
        $preparedServerDescription = trim(substr($description ?? '', 0, 255));

        $pterodactylClientApi
            ->servers
            ->http
            ->post(sprintf('servers/%s/settings/rename', $server->getPterodactylServerIdentifier()), [
                'name' => $preparedServerName,
                'description' => $preparedServerDescription,
            ]);
    }

    public function updateServerEntityName(Server $server, string $serverName): void
    {
        $preparedServerName = trim(substr($serverName, 0, 255));
        $server->setName($preparedServerName);
        $this->serverRepository->save($server);
    }
}
