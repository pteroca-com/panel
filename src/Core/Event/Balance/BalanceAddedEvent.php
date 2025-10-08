<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;

class BalanceAddedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly float $amount,
        private readonly float $oldBalance,
        private readonly float $newBalance,
        private readonly int $paymentId,
        private readonly string $currency,
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getOldBalance(): float
    {
        return $this->oldBalance;
    }

    public function getNewBalance(): float
    {
        return $this->newBalance;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
