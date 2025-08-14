<?php

namespace App\Core\Service;

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

    /**
     * Pobiera istniejące serwery z Pterodactyla i zwraca listę ich ID
     * 
     * @param int $limit Limit serwerów do pobrania
     * @return array Lista pterodactyl_server_id serwerów istniejących w Pterodactylu
     */
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
