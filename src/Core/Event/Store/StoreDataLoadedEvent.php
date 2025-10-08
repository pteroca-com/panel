<?php

namespace App\Core\Event\Store;

use App\Core\Event\AbstractDomainEvent;

class StoreDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?int $userId,
        private readonly array $categories,
        private readonly array $products,
        private readonly int $categoriesCount,
        private readonly int $productsCount,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getCategoriesCount(): int
    {
        return $this->categoriesCount;
    }

    public function getProductsCount(): int
    {
        return $this->productsCount;
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
