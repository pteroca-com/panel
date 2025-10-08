<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerRenewalValidatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly int $priceId,
        private readonly ?int $slots,
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

    public function getPriceId(): int
    {
        return $this->priceId;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }
}
