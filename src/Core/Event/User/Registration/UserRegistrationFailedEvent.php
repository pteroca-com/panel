<?php

namespace App\Core\Event\User\Registration;

use Symfony\Contracts\EventDispatcher\Event;

class UserRegistrationFailedEvent extends Event
{
    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';

    public function __construct(
        private readonly string $email,
        private readonly string $reason,
        private readonly string $stage = 'unknown',
        private readonly array $context = []
    ) {
        $this->eventId = \Symfony\Component\Uid\Uuid::v4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }
}
