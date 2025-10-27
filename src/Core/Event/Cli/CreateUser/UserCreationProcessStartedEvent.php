<?php

namespace App\Core\Event\Cli\CreateUser;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class UserCreationProcessStartedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DateTimeImmutable $startedAt,
        private readonly string $email,
        private readonly string $role,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
