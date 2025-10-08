<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerPurchaseValidatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $productId,
        private readonly int $eggId,
        private readonly int $priceId,
        private readonly ?int $slots,
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

    public function getSlots(): ?int
    {
        return $this->slots;
    }
}
