<?php

namespace App\Core\Event\Server\Network;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class ServerAllocationEditRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly int $allocationId,
        private readonly string $newNotes,
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

    public function getAllocationId(): int
    {
        return $this->allocationId;
    }

    public function getNewNotes(): string
    {
        return $this->newNotes;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
