<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;

class ServerMigrationFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $pterodactylServerId,
        private readonly string $pterodactylServerIdentifier,
        private readonly string $serverName,
        private readonly string $failureReason,
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

    public function getFailureReason(): string
    {
        return $this->failureReason;
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
