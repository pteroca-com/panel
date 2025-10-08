<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartTopUpDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly float $amount,
        private readonly string $currency,
        private readonly array $context = [],
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
