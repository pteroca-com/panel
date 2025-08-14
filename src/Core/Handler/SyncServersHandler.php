<?php

namespace App\Core\Handler;

use App\Core\Service\Sync\PterodactylSyncService;
use App\Core\Service\Sync\PterodactylCleanupService;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncServersHandler implements HandlerInterface
{
    private int $limit = 1000;

    private SymfonyStyle $io;

    public function __construct(
        private readonly PterodactylSyncService $syncService,
        private readonly PterodactylCleanupService $cleanupService,
    )
    {
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;

        return $this;
    }

    public function handle(bool $dryRun = false, bool $auto = false): void
    {
        $this->io->title('PteroCA Server Synchronization');

        if ($dryRun) {
            $this->io->info('Running in dry-run mode - no changes will be made');
        }

        if ($auto) {
            $this->io->info('Running in automatic mode - orphaned servers will be deleted automatically');
        }

        $this->io->section('Fetching servers from Pterodactyl...');
        $existingPterodactylServerIds = $this->syncService->getExistingPterodactylServerIds($this->limit);
        
        $this->io->section('Cleaning up orphaned servers in PteroCA...');
        $deletedServersCount = $this->cleanupService->cleanupOrphanedServers(
            $existingPterodactylServerIds, 
            $auto ? null : $this->io, 
            $dryRun
        );
        
        $this->io->success(sprintf(
            'Server synchronization completed. Found %d existing servers in Pterodactyl, %s %d orphaned servers in PteroCA.',
            count($existingPterodactylServerIds),
            $dryRun ? 'would delete' : 'deleted',
            $deletedServersCount
        ));
    }
}
