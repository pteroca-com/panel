<?php

namespace App\Core\Event\Cli\SuspendUnpaidServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServerAutoRenewedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly DateTimeImmutable $renewedAt,
        private readonly float $renewalCost,
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

    public function getRenewedAt(): DateTimeImmutable
    {
        return $this->renewedAt;
    }

    public function getRenewalCost(): float
    {
        return $this->renewalCost;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
