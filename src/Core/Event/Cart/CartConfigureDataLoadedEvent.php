<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartConfigureDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $productId,
        private readonly array $eggs,
        private readonly bool $hasSlotPrices,
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

    public function getEggs(): array
    {
        return $this->eggs;
    }

    public function hasSlotPrices(): bool
    {
        return $this->hasSlotPrices;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
