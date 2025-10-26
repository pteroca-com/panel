<?php

namespace App\Core\Event\Cli\SyncServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class OrphanedServerDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly int $serverPterodactylServerId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly DateTimeImmutable $deletedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getServerPterodactylServerId(): int
    {
        return $this->serverPterodactylServerId;
    }

    public function getServerPterodactylIdentifier(): string
    {
        return $this->serverPterodactylIdentifier;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getDeletedAt(): DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
