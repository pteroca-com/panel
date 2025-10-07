<?php

namespace App\Core\Event\User\Authentication;

use App\Core\Event\AbstractDomainEvent;

class UserAuthenticationFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $email,
        private readonly string $reason,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getReason(): string
    {
        return $this->reason;
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
