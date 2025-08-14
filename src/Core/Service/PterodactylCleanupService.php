<?php

namespace App\Core\Service;

use App\Core\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PterodactylCleanupService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServerRepository $serverRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Usuwa (oznacza jako deleted_at) serwery z PteroCA, których nie ma w Pterodactylu
     * 
     * @param array $existingPterodactylServerIds Lista pterodactyl_server_id serwerów istniejących w Pterodactylu
     * @param bool $dryRun Jeśli true, nie zapisuje zmian do bazy danych
     * @return int Liczba usuniętych serwerów
     */
    public function cleanupOrphanedServers(array $existingPterodactylServerIds, bool $dryRun = false): int
    {
        $this->logger->info('Starting cleanup of orphaned servers in PteroCA', ['dry_run' => $dryRun]);
        
        if (empty($existingPterodactylServerIds)) {
            $this->logger->warning('No Pterodactyl server IDs provided for cleanup');
            return 0;
        }
        
        // Pobieramy serwery z PteroCA, które nie są usunięte
        // ale których pterodactyl_server_id nie znajduje się w liście serwerów z Pterodactyla
        $orphanedServers = $this->serverRepository->findOrphanedServers($existingPterodactylServerIds);
        
        $deletedCount = 0;
        
        foreach ($orphanedServers as $server) {
            if (!$dryRun) {
                $server->setDeletedAtValue();
            }
            
            $this->logger->info($dryRun ? 'Would mark server as deleted' : 'Marked server as deleted', [
                'server_id' => $server->getId(),
                'pterodactyl_server_id' => $server->getPterodactylServerId(),
                'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                'dry_run' => $dryRun
            ]);
            $deletedCount++;
        }
        
        if ($deletedCount > 0 && !$dryRun) {
            $this->entityManager->flush();
        } elseif ($dryRun && $deletedCount > 0) {
            $this->logger->info('Dry run mode - changes not saved to database');
        }
        
        $this->logger->info('Cleanup completed', ['deleted_servers_count' => $deletedCount]);
        
        return $deletedCount;
    }
}
