<?php

namespace App\Core\Event\Store;

use App\Core\Contract\ProductInterface;
use App\Core\Event\AbstractDomainEvent;

class StoreProductDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?int $userId,
        private readonly int $productId,
        private readonly ProductInterface $product,
        private readonly array $eggs,
        private readonly int $eggsCount,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function getEggs(): array
    {
        return $this->eggs;
    }

    public function getEggsCount(): int
    {
        return $this->eggsCount;
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
