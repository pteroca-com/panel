<?php

namespace App\Core\Service\Sync;

use App\Core\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PterodactylCleanupService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServerRepository $serverRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function cleanupOrphanedServers(array $existingPterodactylServerIds, ?SymfonyStyle $io = null, bool $dryRun = false): int
    {
        $this->logger->info('Starting cleanup of orphaned servers in PteroCA', ['dry_run' => $dryRun]);
        
        if (empty($existingPterodactylServerIds)) {
            $this->logger->warning('No Pterodactyl server IDs provided for cleanup');
            return 0;
        }
        
        $orphanedServers = $this->serverRepository->findOrphanedServers($existingPterodactylServerIds);
        
        $deletedCount = 0;
        
        foreach ($orphanedServers as $server) {
            if ($io && !$this->isUserWantDeleteServer($server, $io)) {
                if ($io) {
                    $io->info(sprintf(
                        'Skipping server #%s (ID: %d)...',
                        $server->getPterodactylServerIdentifier(),
                        $server->getPterodactylServerId()
                    ));
                }
                continue;
            }
            
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
    
    private function isUserWantDeleteServer($server, SymfonyStyle $io): bool
    {
        $questionMessage = sprintf(
            'Server #%s (ID: %d) was not found in Pterodactyl. Do you want to delete it from PteroCA?',
            $server->getPterodactylServerIdentifier(),
            $server->getPterodactylServerId()
        );

        return strtolower($io->ask($questionMessage, 'yes')) === 'yes';
    }
}
