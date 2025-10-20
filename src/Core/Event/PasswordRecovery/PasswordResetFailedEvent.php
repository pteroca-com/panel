<?php

namespace App\Core\Event\PasswordRecovery;

use App\Core\Event\AbstractDomainEvent;

class PasswordResetFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?string $email,
        private readonly string $reason,
        private readonly string $stage,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStage(): string
    {
        return $this->stage;
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
