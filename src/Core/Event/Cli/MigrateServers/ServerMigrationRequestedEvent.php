<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class ServerMigrationRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $pterodactylServerId,
        private readonly string $pterodactylServerIdentifier,
        private readonly string $serverName,
        private readonly string $ownerEmail,
        private readonly int $pterodactylOwnerId,
        private readonly bool $isSuspended,
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

    public function getOwnerEmail(): string
    {
        return $this->ownerEmail;
    }

    public function getPterodactylOwnerId(): int
    {
        return $this->pterodactylOwnerId;
    }

    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
