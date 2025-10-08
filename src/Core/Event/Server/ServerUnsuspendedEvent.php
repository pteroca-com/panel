<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerUnsuspendedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverId,
        private readonly int $userId,
        private readonly int $pterodactylServerId,
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

    public function getPterodactylServerId(): int
    {
        return $this->pterodactylServerId;
    }
}
