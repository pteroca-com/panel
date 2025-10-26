<?php

namespace App\Core\Event\Cli\SyncServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServersSyncProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $pterodactylServersFound,
        private readonly int $orphanedServersFound,
        private readonly int $orphanedServersDeleted,
        private readonly int $orphanedServersSkipped,
        private readonly int $orphanedServersFailed,
        private readonly int $limit,
        private readonly bool $dryRun,
        private readonly bool $auto,
        private readonly int $durationInSeconds,
        private readonly DateTimeImmutable $completedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getPterodactylServersFound(): int
    {
        return $this->pterodactylServersFound;
    }

    public function getOrphanedServersFound(): int
    {
        return $this->orphanedServersFound;
    }

    public function getOrphanedServersDeleted(): int
    {
        return $this->orphanedServersDeleted;
    }

    public function getOrphanedServersSkipped(): int
    {
        return $this->orphanedServersSkipped;
    }

    public function getOrphanedServersFailed(): int
    {
        return $this->orphanedServersFailed;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function isAuto(): bool
    {
        return $this->auto;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getCompletedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
