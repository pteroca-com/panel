<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartTopUpPageAccessedEvent extends AbstractDomainEvent
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
