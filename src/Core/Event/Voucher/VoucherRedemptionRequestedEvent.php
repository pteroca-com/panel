<?php

namespace App\Core\Event\Voucher;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class VoucherRedemptionRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly string $voucherCode,
        private readonly ?float $orderAmount,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getVoucherCode(): string
    {
        return $this->voucherCode;
    }

    public function getOrderAmount(): ?float
    {
        return $this->orderAmount;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getIp(): ?string
    {
        return $this->context['ip'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->context['userAgent'] ?? null;
    }

    public function getLocale(): ?string
    {
        return $this->context['locale'] ?? null;
    }
}
