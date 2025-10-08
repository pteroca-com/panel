<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartRenewDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly bool $isOwner,
        private readonly bool $hasSlotPrices,
        private readonly ?int $serverSlots,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function isOwner(): bool
    {
        return $this->isOwner;
    }

    public function hasSlotPrices(): bool
    {
        return $this->hasSlotPrices;
    }

    public function getServerSlots(): ?int
    {
        return $this->serverSlots;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
