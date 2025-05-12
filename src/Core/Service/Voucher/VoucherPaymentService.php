<?php

namespace App\Core\Service\Voucher;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Voucher;
use App\Core\Enum\VoucherTypeEnum;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherPaymentService
{
    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getVoucher(string $voucherCode): ?Voucher
    {
        try {
            return $this->voucherService->getValidVoucher($voucherCode);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateVoucherCode(string $voucherCode, UserInterface $user, VoucherTypeEnum $voucherType): void
    {
        $redeemResult = $this->voucherService->redeemVoucher($voucherCode, null, $user);
        if (false === $redeemResult->isSuccess()) {
            throw new \Exception($redeemResult->getMessage());
        }

        if ($redeemResult->getType() !== $voucherType->value) {
            throw new \Exception($this->translator->trans('pteroca.voucher.invalid_voucher_type'));
        }
    }

    public function redeemPaymentVoucher(float $amount, string $voucherCode, UserInterface $user): float
    {
        $redeemResult = $this->voucherService->redeemVoucher($voucherCode, $amount, $user);
        if (false === $redeemResult->isSuccess()) {
            throw new \Exception($redeemResult->getMessage());
        }

        $voucher = $this->voucherService->getValidVoucher($voucherCode);
        $this->voucherService->redeemVoucherForUser($voucher, $user);

        return $amount * (1 - $redeemResult->getValue() / 100);
    }
}
