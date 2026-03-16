<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerRenewalBeforeBalanceChargeEvent extends AbstractDomainEvent
{
    private bool $balanceChargeSkipped = false;
    private ?string $alternativePaymentMethod = null;

    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly float $amount,
        private readonly string $currency,
        private readonly ?string $voucherCode,
    ) {
        parent::__construct();
    }

    public function skipBalanceCharge(string $alternativePaymentMethod): void
    {
        $this->balanceChargeSkipped = true;
        $this->alternativePaymentMethod = $alternativePaymentMethod;
    }

    public function isBalanceChargeSkipped(): bool
    {
        return $this->balanceChargeSkipped;
    }

    public function getAlternativePaymentMethod(): ?string
    {
        return $this->alternativePaymentMethod;
    }

    public function getUserId(): int
    {
        return $this->userId;
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

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }
}
