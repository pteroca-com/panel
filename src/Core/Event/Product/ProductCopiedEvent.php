<?php

namespace App\Core\Event\Product;

use App\Core\Event\AbstractDomainEvent;

class ProductCopiedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $originalProductId,
        private readonly int $copiedProductId,
        private readonly string $copiedProductName,
        private readonly int $pricesCount,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOriginalProductId(): int
    {
        return $this->originalProductId;
    }

    public function getCopiedProductId(): int
    {
        return $this->copiedProductId;
    }

    public function getCopiedProductName(): string
    {
        return $this->copiedProductName;
    }

    public function getPricesCount(): int
    {
        return $this->pricesCount;
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
