<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;

class BalanceRechargePageAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly float $currentBalance,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCurrentBalance(): float
    {
        return $this->currentBalance;
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
