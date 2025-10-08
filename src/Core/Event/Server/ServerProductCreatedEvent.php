<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerProductCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverProductId,
        private readonly int $serverId,
        private readonly int $originalProductId,
    ) {
        parent::__construct();
    }

    public function getServerProductId(): int
    {
        return $this->serverProductId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getOriginalProductId(): int
    {
        return $this->originalProductId;
    }
}
