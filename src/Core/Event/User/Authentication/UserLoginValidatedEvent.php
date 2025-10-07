<?php

namespace App\Core\Event\User\Authentication;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class UserLoginValidatedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly string $email,
        private readonly int $userId,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getIp(): ?string
    {
        return $this->context['ip'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->context['userAgent'] ?? null;
    }
}
