<?php

namespace App\Core\Event\Balance;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class BalanceAboutToBeAddedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private float $amount,
        private readonly float $oldBalance,
        private readonly float $newBalance,
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getOldBalance(): float
    {
        return $this->oldBalance;
    }

    public function getNewBalance(): float
    {
        return $this->newBalance;
    }
}
