<?php

namespace App\Core\Event\Cli\SuspendUnpaidServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class ServerSuspendedForNonPaymentEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly DateTimeImmutable $suspendedAt,
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

    public function getSuspendedAt(): DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
