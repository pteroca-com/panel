<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylService;

class AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
    )
    {
    }

    protected function getServerDetails(Server $server, array $include = []): array
    {
        $serverDetails = $this->pterodactylService->getApi()->servers->get($server->getPterodactylServerId(), [
            'include' => $include,
        ])?->toArray();

        if (empty($serverDetails)) {
            throw new \Exception('Server not found');
        }

        return $serverDetails;
    }
}
