<?php

namespace App\Core\Event\Cli\MigrateServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServerMigratedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly int $pterodactylServerId,
        private readonly string $pterodactylServerIdentifier,
        private readonly string $serverName,
        private readonly int $duration,
        private readonly float $price,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $migratedAt,
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

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getMigratedAt(): DateTimeImmutable
    {
        return $this->migratedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
