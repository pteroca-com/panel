<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;

class BalancePaymentValidatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $sessionId,
        private readonly float $amount,
        private readonly string $currency,
        private readonly string $paymentStatus,
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }
}
