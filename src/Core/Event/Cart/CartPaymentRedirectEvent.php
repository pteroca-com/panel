<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartPaymentRedirectEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly float $amount,
        private readonly string $currency,
        private readonly string $paymentUrl,
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

    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
