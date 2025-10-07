<?php

namespace App\Core\Event\User;

use App\Core\Event\StoppableEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserRegistrationValidatedEvent extends Event
{
    use StoppableEventTrait;

    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';
    private array $roles;

    public function __construct(
        private readonly string $email,
        private readonly string $normalizedEmail,
        array $roles = ['ROLE_USER'],
        private readonly array $context = []
    ) {
        $this->eventId = \Symfony\Component\Uid\Uuid::v4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
        $this->roles = $roles;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getNormalizedEmail(): string
    {
        return $this->normalizedEmail;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
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
