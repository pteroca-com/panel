<?php

namespace App\Core\Event\PasswordRecovery;

use App\Core\Event\AbstractDomainEvent;

class PasswordResetValidatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly bool $tokenValid,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function isTokenValid(): bool
    {
        return $this->tokenValid;
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

    public function getLocale(): ?string
    {
        return $this->context['locale'] ?? null;
    }
}
