<?php

namespace App\Core\Event\Product;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class ProductCopyRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $originalProductId,
        private readonly string $originalProductName,
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

    public function getOriginalProductName(): string
    {
        return $this->originalProductName;
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
