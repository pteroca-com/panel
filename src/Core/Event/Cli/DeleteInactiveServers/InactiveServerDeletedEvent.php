<?php

namespace App\Core\Event\Cli\DeleteInactiveServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class InactiveServerDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly DateTimeImmutable $expiredAt,
        private readonly DateTimeImmutable $deletedAt,
        private readonly int $daysAfterExpiration,
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

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getExpiredAt(): DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getDeletedAt(): DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getDaysAfterExpiration(): int
    {
        return $this->daysAfterExpiration;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
