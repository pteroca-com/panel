<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerPurchaseCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverId,
        private readonly int $userId,
        private readonly int $productId,
        private readonly float $pricePaid,
    ) {
        parent::__construct();
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getPricePaid(): float
    {
        return $this->pricePaid;
    }
}
