<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerBalanceChargedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly float $oldBalance,
        private readonly float $newBalance,
        private readonly int $serverId,
        private readonly float $amount,
        private readonly string $currency,
    ) {
        parent::__construct();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOldBalance(): float
    {
        return $this->oldBalance;
    }

    public function getNewBalance(): float
    {
        return $this->newBalance;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
