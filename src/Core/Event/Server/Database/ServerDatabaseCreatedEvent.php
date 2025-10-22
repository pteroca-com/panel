<?php

namespace App\Core\Event\Server\Database;

use App\Core\Event\AbstractDomainEvent;

class ServerDatabaseCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $databaseName,
        private readonly string $connectionsFrom,
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

    public function getServerPterodactylIdentifier(): string
    {
        return $this->serverPterodactylIdentifier;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getConnectionsFrom(): string
    {
        return $this->connectionsFrom;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
