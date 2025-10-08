<?php

namespace App\Core\Event\Cart;

use App\Core\Event\AbstractDomainEvent;

class CartRenewBuyRequestedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly ?string $voucherCode,
        private readonly ?int $slots,
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

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
