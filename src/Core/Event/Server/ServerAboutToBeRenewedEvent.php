<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;
use DateTimeInterface;

class ServerAboutToBeRenewedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly DateTimeInterface $currentExpiresAt,
        private readonly DateTimeInterface $newExpiresAt,
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

    public function getCurrentExpiresAt(): DateTimeInterface
    {
        return $this->currentExpiresAt;
    }

    public function getNewExpiresAt(): DateTimeInterface
    {
        return $this->newExpiresAt;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }
}
