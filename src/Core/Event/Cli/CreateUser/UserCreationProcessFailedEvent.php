<?php

namespace App\Core\Event\Cli\CreateUser;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class UserCreationProcessFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $failureReason,
        private readonly string $email,
        private readonly string $role,
        private readonly DateTimeImmutable $failedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getFailedAt(): DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
