<?php

namespace App\Core\Event\Cli\SyncServers;

use App\Core\Event\AbstractDomainEvent;

class OrphanedServerSkippedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly int $serverPterodactylServerId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly string $reason,
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

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
