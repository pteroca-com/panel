<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServerMigrationProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $pterodactylServersFound,
        private readonly int $pterodactylUsersFound,
        private readonly int $serversAlreadyExisting,
        private readonly int $serversMigrated,
        private readonly int $serversSkipped,
        private readonly int $serversFailed,
        private readonly int $limit,
        private readonly bool $dryRun,
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

    public function getPterodactylUsersFound(): int
    {
        return $this->pterodactylUsersFound;
    }

    public function getServersAlreadyExisting(): int
    {
        return $this->serversAlreadyExisting;
    }

    public function getServersMigrated(): int
    {
        return $this->serversMigrated;
    }

    public function getServersSkipped(): int
    {
        return $this->serversSkipped;
    }

    public function getServersFailed(): int
    {
        return $this->serversFailed;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
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
