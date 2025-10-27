<?php

namespace App\Core\Event\Voucher;

use App\Core\Event\AbstractDomainEvent;

class VoucherDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $voucherId,
        private readonly string $voucherCode,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getVoucherId(): int
    {
        return $this->voucherId;
    }

    public function getVoucherCode(): string
    {
        return $this->voucherCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
