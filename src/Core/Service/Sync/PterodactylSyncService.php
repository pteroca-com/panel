<?php

namespace App\Core\Service\Sync;

use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Psr\Log\LoggerInterface;

class PterodactylSyncService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getExistingPterodactylServerIds(int $limit = 1000): array
    {
        $this->logger->info('Fetching existing servers from Pterodactyl');

        $pterodactylServers = $this->pterodactylApplicationService->allServers([
            'per_page' => $limit,
        ]);
        
        $existingServerIds = [];
        foreach ($pterodactylServers as $server) {
            $existingServerIds[] = $server['id'];
        }
        
        $this->logger->info('Found existing servers in Pterodactyl', [
            'count' => count($existingServerIds)
        ]);
        
        return $existingServerIds;
    }
}
