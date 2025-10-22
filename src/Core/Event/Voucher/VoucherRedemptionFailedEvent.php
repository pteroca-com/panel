<?php

namespace App\Core\Event\Voucher;

use App\Core\Enum\VoucherTypeEnum;
use App\Core\Event\AbstractDomainEvent;

class VoucherRedemptionFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $voucherCode,
        private readonly string $failureReason,
        private readonly ?VoucherTypeEnum $attemptedVoucherType,
        private readonly ?float $attemptedVoucherValue,
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

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function getAttemptedVoucherType(): ?VoucherTypeEnum
    {
        return $this->attemptedVoucherType;
    }

    public function getAttemptedVoucherValue(): ?float
    {
        return $this->attemptedVoucherValue;
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
