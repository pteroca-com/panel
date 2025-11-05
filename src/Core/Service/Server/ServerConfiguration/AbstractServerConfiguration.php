<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;

class AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
    )
    {
    }

    /**
     * @throws Exception
     */
    protected function getServerDetails(Server $server, array $include = []): array
    {
        $serverDetails = $this->pterodactylApplicationService
            ->getApplicationApi()
            ->servers()
            ->getServer($server->getPterodactylServerId(), $include)
            ?->toArray();

        if (empty($serverDetails)) {
            throw new Exception('Server not found');
        }

        return $serverDetails;
    }
}
