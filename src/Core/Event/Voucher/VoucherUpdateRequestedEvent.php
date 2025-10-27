<?php

namespace App\Core\Event\Voucher;

use App\Core\Entity\Voucher;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class VoucherUpdateRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly Voucher $voucher,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    public function getVoucherId(): ?int
    {
        return $this->voucher->getId();
    }

    public function getVoucherCode(): string
    {
        return $this->voucher->getCode();
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
