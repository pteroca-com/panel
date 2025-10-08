<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartConfigurePageAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $productId,
        private readonly string $productName,
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

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
