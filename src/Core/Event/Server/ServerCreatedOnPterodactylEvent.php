<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerCreatedOnPterodactylEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $pterodactylServerId,
        private readonly string $pterodactylServerIdentifier,
        private readonly int $productId,
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPterodactylServerId(): int
    {
        return $this->pterodactylServerId;
    }

    public function getPterodactylServerIdentifier(): string
    {
        return $this->pterodactylServerIdentifier;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }
}
