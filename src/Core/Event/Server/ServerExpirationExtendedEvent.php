<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;
use DateTimeInterface;

class ServerExpirationExtendedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverId,
        private readonly int $userId,
        private readonly DateTimeInterface $oldExpiresAt,
        private readonly DateTimeInterface $newExpiresAt,
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

    public function getOldExpiresAt(): DateTimeInterface
    {
        return $this->oldExpiresAt;
    }

    public function getNewExpiresAt(): DateTimeInterface
    {
        return $this->newExpiresAt;
    }
}
