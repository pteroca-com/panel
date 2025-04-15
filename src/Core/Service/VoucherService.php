<?php

namespace App\Core\Service;

use App\Core\DTO\Action\Result\RedeemVoucherActionResult;
use App\Core\Entity\User;
use App\Core\Entity\Voucher;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\VoucherRepository;
use App\Core\Repository\VoucherUsageRepository;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherService
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly VoucherUsageRepository $voucherUsageRepository,
        private readonly ServerRepository $serverRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function redeemVoucher(string $code, User $user): RedeemVoucherActionResult
    {
        try {
            $voucher = $this->getValidVoucher($code);
            $this->validateNewAccountRequirementIfNeeded($voucher, $user);
            $this->validateOneUsePerUserRequirementIfNeeded($voucher, $user);

            // TODO check if voucher minimum values are met

            // TODO redeem voucher

            $successMessage = $this->translator->trans('pteroca.api.voucher.successfully_applied');

            return RedeemVoucherActionResult::success($successMessage);
        } catch (Exception $exception) {
            return RedeemVoucherActionResult::failure($exception->getMessage());
        }
    }

    private function getValidVoucher(string $code): Voucher
    {
        $voucher = $this->voucherRepository->getVoucherByCode($code);

        if (empty($voucher) || !empty($voucher->getDeletedAt())) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.not_found'));
        }

        if ($voucher->getExpirationDate() < new \DateTime()) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.expired'));
        }

        if (!empty($voucher->getMaxGlobalUses()) && $voucher->getUsedCount() >= $voucher->getMaxGlobalUses()) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.max_global_uses_reached'));
        }

        return $voucher;
    }

    private function validateNewAccountRequirementIfNeeded(Voucher $voucher, User $user): void
    {
        if ($voucher->isNewAccountsOnly()) {
            if ($voucher->getType() === VoucherTypeEnum::DISCOUNT && $this->serverRepository->getAllServersOwnedCount($user->getId()) > 0) {
                // TODO throw exception
            }

            if ($voucher->getType() === VoucherTypeEnum::BALANCE_TOPUP) { // TODO check balance history
                // TODO throw exception
            }
        }
    }

    private function validateOneUsePerUserRequirementIfNeeded(Voucher $voucher, User $user): void
    {
        if (false === $voucher->isOneUsePerUser()) {
            return;
        }

        if ($this->voucherUsageRepository->hasUsedVoucher($voucher->getCode(), $user->getId())) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.already_used'));
        }
    }
}
