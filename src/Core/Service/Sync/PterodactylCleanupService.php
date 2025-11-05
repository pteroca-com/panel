<?php

namespace App\Core\Service\Sync;

use App\Core\Event\Cli\SyncServers\OrphanedServerDeletedEvent;
use App\Core\Event\Cli\SyncServers\OrphanedServerDeletionFailedEvent;
use App\Core\Event\Cli\SyncServers\OrphanedServerFoundEvent;
use App\Core\Event\Cli\SyncServers\OrphanedServerSkippedEvent;
use App\Core\Repository\ServerRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class PterodactylCleanupService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerRepository       $serverRepository,
        private LoggerInterface        $logger
    ) {
    }

    public function cleanupOrphanedServers(
        array $existingPterodactylServerIds,
        ?SymfonyStyle $io = null,
        bool $dryRun = false,
        ?EventDispatcherInterface $eventDispatcher = null,
        array $context = [],
        ?array &$stats = null
    ): int {
        $this->logger->info('Starting cleanup of orphaned servers in PteroCA', ['dry_run' => $dryRun]);

        if (empty($existingPterodactylServerIds)) {
            $this->logger->warning('No Pterodactyl server IDs provided for cleanup');
            return 0;
        }

        $orphanedServers = $this->serverRepository->findOrphanedServers($existingPterodactylServerIds);

        $deletedCount = 0;

        foreach ($orphanedServers as $server) {
            if ($stats !== null) {
                $stats['orphanedServersFound']++;
            }

            try {
                if ($eventDispatcher) {
                    $foundEvent = new OrphanedServerFoundEvent(
                        $server->getUser()->getId() ?? 0,
                        $server->getId(),
                        $server->getPterodactylServerId(),
                        $server->getPterodactylServerIdentifier(),
                        $server->getName(),
                        $context
                    );
                    $eventDispatcher->dispatch($foundEvent);

                    if ($foundEvent->isPropagationStopped()) {
                        if ($stats !== null) {
                            $stats['orphanedServersSkipped']++;
                        }

                        $eventDispatcher->dispatch(
                            new OrphanedServerSkippedEvent(
                                $server->getUser()->getId() ?? 0,
                                $server->getId(),
                                $server->getPterodactylServerId(),
                                $server->getPterodactylServerIdentifier(),
                                $server->getName(),
                                'plugin_blocked',
                                $context
                            )
                        );
                        continue;
                    }
                }

                if ($io && !$this->isUserWantDeleteServer($server, $io)) {
                    $io->info(sprintf(
                        'Skipping server #%s (ID: %d)...',
                        $server->getPterodactylServerIdentifier(),
                        $server->getPterodactylServerId()
                    ));

                    if ($stats !== null) {
                        $stats['orphanedServersSkipped']++;
                    }

                    $eventDispatcher?->dispatch(
                        new OrphanedServerSkippedEvent(
                            $server->getUser()->getId() ?? 0,
                            $server->getId(),
                            $server->getPterodactylServerId(),
                            $server->getPterodactylServerIdentifier(),
                            $server->getName(),
                            'user_declined',
                            $context
                        )
                    );
                    continue;
                }

                if ($dryRun) {
                    if ($stats !== null) {
                        $stats['orphanedServersSkipped']++;
                    }

                    $eventDispatcher?->dispatch(
                        new OrphanedServerSkippedEvent(
                            $server->getUser()->getId() ?? 0,
                            $server->getId(),
                            $server->getPterodactylServerId(),
                            $server->getPterodactylServerIdentifier(),
                            $server->getName(),
                            'dry_run',
                            $context
                        )
                    );

                    $this->logger->info('Would mark server as deleted', [
                        'server_id' => $server->getId(),
                        'pterodactyl_server_id' => $server->getPterodactylServerId(),
                        'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                        'dry_run' => true
                    ]);
                    $deletedCount++;
                    continue;
                }

                $server->setDeletedAtValue();

                $this->logger->info('Marked server as deleted', [
                    'server_id' => $server->getId(),
                    'pterodactyl_server_id' => $server->getPterodactylServerId(),
                    'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                    'dry_run' => false
                ]);
                $deletedCount++;

                if ($stats !== null) {
                    $stats['orphanedServersDeleted']++;
                }

                $eventDispatcher?->dispatch(
                    new OrphanedServerDeletedEvent(
                        $server->getUser()->getId() ?? 0,
                        $server->getId(),
                        $server->getPterodactylServerId(),
                        $server->getPterodactylServerIdentifier(),
                        $server->getName(),
                        new DateTimeImmutable(),
                        $context
                    )
                );

            } catch (Exception $e) {
                if ($stats !== null) {
                    $stats['orphanedServersFailed']++;
                }

                $eventDispatcher?->dispatch(
                    new OrphanedServerDeletionFailedEvent(
                        $server->getUser()->getId() ?? 0,
                        $server->getId(),
                        $server->getPterodactylServerId(),
                        $server->getPterodactylServerIdentifier(),
                        $server->getName(),
                        $e->getMessage(),
                        $context
                    )
                );

                $this->logger->error('Failed to delete server', [
                    'server_id' => $server->getId(),
                    'error' => $e->getMessage()
                ]);

                // Continue processing other servers
            }
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
