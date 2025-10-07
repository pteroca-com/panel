<?php

namespace App\Core\Event\User\Registration;

use Symfony\Contracts\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';

    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly bool $isVerified = false,
        private readonly array $context = []
    ) {
        $this->eventId = \Symfony\Component\Uid\Uuid::v4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
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
