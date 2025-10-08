<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartRenewPageAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverName,
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

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
