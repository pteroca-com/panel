<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartBuyRequestedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $productId,
        private readonly int $eggId,
        private readonly int $priceId,
        private readonly string $serverName,
        private readonly bool $autoRenewal,
        private readonly ?int $slots,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getEggId(): int
    {
        return $this->eggId;
    }

    public function getPriceId(): int
    {
        return $this->priceId;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function isAutoRenewal(): bool
    {
        return $this->autoRenewal;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
