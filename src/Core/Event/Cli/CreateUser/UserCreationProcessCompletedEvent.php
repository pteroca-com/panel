<?php

namespace App\Core\Event\Cli\CreateUser;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class UserCreationProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly string $role,
        private readonly bool $hasPterodactylAccount,
        private readonly bool $hasApiKey,
        private readonly bool $createdWithoutApiKey,
        private readonly int $durationInSeconds,
        private readonly DateTimeImmutable $completedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function hasPterodactylAccount(): bool
    {
        return $this->hasPterodactylAccount;
    }

    public function hasApiKey(): bool
    {
        return $this->hasApiKey;
    }

    public function isCreatedWithoutApiKey(): bool
    {
        return $this->createdWithoutApiKey;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getCompletedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
