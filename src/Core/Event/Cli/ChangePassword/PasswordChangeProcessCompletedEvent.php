<?php

namespace App\Core\Event\Cli\ChangePassword;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class PasswordChangeProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly bool $passwordChangedInPterodactyl,
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

    public function isPasswordChangedInPterodactyl(): bool
    {
        return $this->passwordChangedInPterodactyl;
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
