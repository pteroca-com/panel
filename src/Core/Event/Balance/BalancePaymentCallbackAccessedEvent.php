<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;

class BalancePaymentCallbackAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly ?string $sessionId,
        private readonly string $callbackType,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getCallbackType(): string
    {
        return $this->callbackType;
    }

    public function isSuccess(): bool
    {
        return $this->callbackType === 'success';
    }

    public function isCancel(): bool
    {
        return $this->callbackType === 'cancel';
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
