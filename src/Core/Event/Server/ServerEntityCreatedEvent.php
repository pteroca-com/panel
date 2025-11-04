<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;
use DateTimeInterface;

class ServerEntityCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverId,
        private readonly int $userId,
        private readonly int $pterodactylServerId,
        private readonly DateTimeInterface $expiresAt,
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

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expiresAt;
    }
}
