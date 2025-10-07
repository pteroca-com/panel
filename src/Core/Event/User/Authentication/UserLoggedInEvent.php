<?php

namespace App\Core\Event\User\Authentication;

use App\Core\Event\AbstractDomainEvent;

class UserLoggedInEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly ?string $sessionId = null,
        private readonly bool $rememberMe = false,
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

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
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
