<?php

namespace App\Core\Event\Voucher;

use App\Core\Entity\Voucher;
use App\Core\Event\AbstractDomainEvent;

class VoucherCreatedEvent extends AbstractDomainEvent
{
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

    public function getVoucherValue(): string
    {
        return $this->voucher->getValue();
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
