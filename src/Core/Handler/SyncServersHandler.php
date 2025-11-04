<?php

namespace App\Core\Handler;

use App\Core\Event\Cli\SyncServers\ServersSyncProcessCompletedEvent;
use App\Core\Event\Cli\SyncServers\ServersSyncProcessFailedEvent;
use App\Core\Event\Cli\SyncServers\ServersSyncProcessStartedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Sync\PterodactylSyncService;
use App\Core\Service\Sync\PterodactylCleanupService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncServersHandler implements HandlerInterface
{
    private int $limit = 1000;

    private SymfonyStyle $io;

    public function __construct(
        private readonly PterodactylSyncService $syncService,
        private readonly PterodactylCleanupService $cleanupService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
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

    /**
     * @throws Exception
     */
    public function handle(bool $dryRun = false, bool $auto = false): void
    {
        $startTime = new DateTimeImmutable();
        $context = $this->eventContextService->buildCliContext('pteroca:sync-servers', [
            'limit' => $this->limit,
            'dryRun' => $dryRun,
            'auto' => $auto,
        ]);

        $this->eventDispatcher->dispatch(
            new ServersSyncProcessStartedEvent($startTime, $this->limit, $dryRun, $auto, $context)
        );

        $stats = [
            'pterodactylServersFound' => 0,
            'orphanedServersFound' => 0,
            'orphanedServersDeleted' => 0,
            'orphanedServersSkipped' => 0,
            'orphanedServersFailed' => 0,
        ];

        try {
            $this->io->title('PteroCA Server Synchronization');

            if ($dryRun) {
                $this->io->info('Running in dry-run mode - no changes will be made');
            }

            if ($auto) {
                $this->io->info('Running in automatic mode - orphaned servers will be deleted automatically');
            }

            $this->io->section('Fetching servers from Pterodactyl...');
            $existingPterodactylServerIds = $this->syncService->getExistingPterodactylServerIds($this->limit);
            $stats['pterodactylServersFound'] = count($existingPterodactylServerIds);

            $this->io->section('Cleaning up orphaned servers in PteroCA...');
            $this->cleanupService->cleanupOrphanedServers(
                $existingPterodactylServerIds,
                $auto ? null : $this->io,
                $dryRun,
                $this->eventDispatcher,
                $context,
                $stats
            );

            $duration = (new DateTimeImmutable())->getTimestamp() - $startTime->getTimestamp();
            $this->eventDispatcher->dispatch(
                new ServersSyncProcessCompletedEvent(
                    $stats['pterodactylServersFound'],
                    $stats['orphanedServersFound'],
                    $stats['orphanedServersDeleted'],
                    $stats['orphanedServersSkipped'],
                    $stats['orphanedServersFailed'],
                    $this->limit,
                    $dryRun,
                    $auto,
                    $duration,
                    new DateTimeImmutable(),
                    $context
                )
            );

            $this->io->success(sprintf(
                'Server synchronization completed. Found %d existing servers in Pterodactyl, %s %d orphaned servers in PteroCA. (Skipped: %d, Failed: %d)',
                $stats['pterodactylServersFound'],
                $dryRun ? 'would delete' : 'deleted',
                $dryRun ? $stats['orphanedServersSkipped'] : $stats['orphanedServersDeleted'],
                $stats['orphanedServersSkipped'],
                $stats['orphanedServersFailed']
            ));

        } catch (Exception $e) {
            $this->eventDispatcher->dispatch(
                new ServersSyncProcessFailedEvent(
                    $e->getMessage(),
                    $stats,
                    new DateTimeImmutable(),
                    $context
                )
            );
            throw $e;
        }
    }
}
