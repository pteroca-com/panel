<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;

class PaymentFinalizedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $paymentId,
        private readonly int $userId,
        private readonly float $amount,
        private readonly string $currency,
        private readonly float $balanceAmount,
        private readonly string $sessionId,
    ) {
        parent::__construct();
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBalanceAmount(): float
    {
        return $this->balanceAmount;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}
