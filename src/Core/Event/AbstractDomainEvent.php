<?php

namespace App\Core\Event;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDomainEvent
{
    private string $eventId;
    private DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';

    public function __construct(?string $eventId = null)
    {
        $this->eventId = $eventId ?? Uuid::v4()->toString();
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }
}
