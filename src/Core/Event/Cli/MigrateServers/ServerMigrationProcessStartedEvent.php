<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServerMigrationProcessStartedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DateTimeImmutable $startedAt,
        private readonly int $limit,
        private readonly bool $dryRun,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
