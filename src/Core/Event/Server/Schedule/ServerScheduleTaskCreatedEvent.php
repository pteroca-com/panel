<?php

namespace App\Core\Event\Server\Schedule;

use App\Core\Event\AbstractDomainEvent;

class ServerScheduleTaskCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly int $scheduleId,
        private readonly int $taskId,
        private readonly string $action,
        private readonly string $payload,
        private readonly int $timeOffset,
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

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getTimeOffset(): int
    {
        return $this->timeOffset;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
