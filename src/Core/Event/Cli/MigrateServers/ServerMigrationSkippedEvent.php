<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;

class ServerMigrationSkippedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $pterodactylServerId,
        private readonly string $pterodactylServerIdentifier,
        private readonly string $serverName,
        private readonly string $reason,
        private readonly ?string $ownerEmail,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getPterodactylServerId(): int
    {
        return $this->pterodactylServerId;
    }

    public function getPterodactylServerIdentifier(): string
    {
        return $this->pterodactylServerIdentifier;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getOwnerEmail(): ?string
    {
        return $this->ownerEmail;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
