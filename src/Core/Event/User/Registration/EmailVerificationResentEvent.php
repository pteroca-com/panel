<?php

namespace App\Core\Event\User\Registration;

use App\Core\Event\AbstractDomainEvent;

class EmailVerificationResentEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly int $resendCount,
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

    public function getResendCount(): int
    {
        return $this->resendCount;
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
