<?php

namespace App\Core\Event\Server\Schedule;

use App\Core\Event\AbstractDomainEvent;

class ServerScheduleUpdatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly int $scheduleId,
        private readonly string $scheduleName,
        private readonly array $cronExpression,
        private readonly bool $isActive,
        private readonly bool $onlyWhenOnline,
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

    public function getScheduleId(): int
    {
        return $this->scheduleId;
    }

    public function getScheduleName(): string
    {
        return $this->scheduleName;
    }

    public function getCronExpression(): array
    {
        return $this->cronExpression;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isOnlyWhenOnline(): bool
    {
        return $this->onlyWhenOnline;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
