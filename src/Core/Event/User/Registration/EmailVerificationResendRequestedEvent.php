<?php

namespace App\Core\Event\User\Registration;

use App\Core\Event\AbstractDomainEvent;
use DateTimeInterface;

class EmailVerificationResendRequestedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int                $userId,
        private readonly string             $email,
        private readonly ?DateTimeInterface $lastSentAt,
        private readonly bool               $canResend,
        private readonly array              $context = [],
        ?string                             $eventId = null,
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

    public function getLastSentAt(): ?DateTimeInterface
    {
        return $this->lastSentAt;
    }

    public function canResend(): bool
    {
        return $this->canResend;
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
