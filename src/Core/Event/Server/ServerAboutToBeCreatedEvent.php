<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class ServerAboutToBeCreatedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $productId,
        private readonly string $serverName,
        private readonly int $eggId,
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

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getEggId(): int
    {
        return $this->eggId;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }
}
