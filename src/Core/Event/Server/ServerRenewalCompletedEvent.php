<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;
use DateTimeInterface;

class ServerRenewalCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serverId,
        private readonly int $userId,
        private readonly float $pricePaid,
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

    public function getPricePaid(): float
    {
        return $this->pricePaid;
    }

    public function getNewExpiresAt(): DateTimeInterface
    {
        return $this->newExpiresAt;
    }
}
