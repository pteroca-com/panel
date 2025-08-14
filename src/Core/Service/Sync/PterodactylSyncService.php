<?php

namespace App\Core\Service\Sync;

use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use Psr\Log\LoggerInterface;

class PterodactylSyncService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getExistingPterodactylServerIds(int $limit = 1000): array
    {
        $this->logger->info('Fetching existing servers from Pterodactyl');
        
        $pterodactylApi = $this->pterodactylService->getApi();
        $pterodactylServers = $pterodactylApi->servers->all([
            'per_page' => $limit,
        ]);
        
        $existingServerIds = [];
        foreach ($pterodactylServers->toArray() as $server) {
            $existingServerIds[] = $server['id'];
        }
        
        $this->logger->info('Found existing servers in Pterodactyl', [
            'count' => count($existingServerIds)
        ]);
        
        return $existingServerIds;
    }
}
