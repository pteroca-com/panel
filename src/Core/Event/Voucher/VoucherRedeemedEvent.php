<?php

namespace App\Core\Event\Voucher;

use App\Core\Enum\VoucherTypeEnum;
use App\Core\Event\AbstractDomainEvent;

class VoucherRedeemedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $voucherId,
        private readonly string $voucherCode,
        private readonly VoucherTypeEnum $voucherType,
        private readonly float $voucherValue,
        private readonly int $voucherUsageId,
        private readonly ?float $balanceAdded,
        private readonly ?float $oldBalance,
        private readonly ?float $newBalance,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getVoucherId(): int
    {
        return $this->voucherId;
    }

    public function getVoucherCode(): string
    {
        return $this->voucherCode;
    }

    public function getVoucherType(): VoucherTypeEnum
    {
        return $this->voucherType;
    }

    public function getVoucherValue(): float
    {
        return $this->voucherValue;
    }

    public function getVoucherUsageId(): int
    {
        return $this->voucherUsageId;
    }

    public function getBalanceAdded(): ?float
    {
        return $this->balanceAdded;
    }

    public function getOldBalance(): ?float
    {
        return $this->oldBalance;
    }

    public function getNewBalance(): ?float
    {
        return $this->newBalance;
    }

    public function isBalanceTopup(): bool
    {
        return $this->voucherType === VoucherTypeEnum::BALANCE_TOPUP;
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
