<?php

namespace App\Core\Event\Cli\DeleteInactiveServers;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;
use DateTimeImmutable;

class InactiveServerDeletionRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly DateTimeImmutable $expiredAt,
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

    public function getDaysAfterExpiration(): int
    {
        return $this->daysAfterExpiration;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
