<?php

namespace App\Core\Event\Voucher;

use App\Core\Entity\Voucher;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class VoucherCreationRequestedEvent extends AbstractDomainEvent
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

    public function getVoucherType(): string
    {
        return $this->voucher->getType()->value;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
