<?php

namespace App\Core\Service;

use App\Core\DTO\Action\Result\RedeemVoucherActionResult;
use App\Core\Entity\Payment;
use App\Core\Entity\User;
use App\Core\Entity\Voucher;
use App\Core\Entity\VoucherUsage;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Repository\PaymentRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Repository\VoucherRepository;
use App\Core\Repository\VoucherUsageRepository;
use App\Core\Service\Logs\LogService;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherService
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly VoucherUsageRepository $voucherUsageRepository,
        private readonly ServerRepository $serverRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly SettingService $settingService,
        private readonly UserRepository $userRepository,
        private readonly LogService $logService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function redeemVoucher(string $code, User $user): RedeemVoucherActionResult
    {
        try {
            $voucher = $this->getValidVoucher($code);
            $this->validateNewAccountRequirementIfNeeded($voucher, $user);
            $this->validateOneUsePerUserRequirementIfNeeded($voucher, $user);
            $this->validateMinimumTopupAmountRequirementIfNeeded($voucher, $user);

            if ($voucher->getType() === VoucherTypeEnum::BALANCE_TOPUP) {
                $this->redeemVoucherForUser($voucher, $user);
            }

            $successMessage = $this->translator->trans('pteroca.api.voucher.successfully_applied');

            return RedeemVoucherActionResult::success(
                $successMessage,
                $voucher->getType()->value,
                $voucher->getValue(),
            );
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

        if (!empty($voucher->getExpirationDate()) && $voucher->getExpirationDate() < new \DateTime()) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.expired'));
        }

        if (!empty($voucher->getMaxGlobalUses()) && $voucher->getUsedCount() >= $voucher->getMaxGlobalUses()) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.max_global_uses_reached'));
        }

        return $voucher;
    }

    private function validateNewAccountRequirementIfNeeded(Voucher $voucher, User $user): void
    {
        if (false === $voucher->isNewAccountsOnly()) {
            return;
        }

        if (
            $this->voucherUsageRepository->hasUsedAnyVoucher($user->getId())
            || $this->serverRepository->getAllServersOwnedCount($user->getId()) > 0
            || $this->paymentRepository->getUserSuccessfulPaymentsCount($user->getId()) > 0
        ) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.only_for_new_accounts'));
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

    private function validateMinimumTopupAmountRequirementIfNeeded(Voucher $voucher, User $user): void
    {
        if (empty($voucher->getMinimumTopupAmount())) {
            return;
        }

        $userPayments = $this->paymentRepository->getUserSuccessfulPayments($user);
        $userPaymentsSum = array_reduce($userPayments, function ($carry, Payment $payment) {
            return $carry + $payment->getAmount();
        }, 0);

        if ($voucher->getMinimumTopupAmount() > $userPaymentsSum) {
            $exceptionMessage = $this->translator->trans('pteroca.api.voucher.minimum_top_up_amount_required', [
                '{{ amount }}' => $voucher->getMinimumTopupAmount(),
                '{{ currency }}' => $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
            ]);

            throw new Exception($exceptionMessage);
        }
    }

    private function validateMinimumOrderAmountRequirementIfNeeded(Voucher $voucher, User $user): void // TODO sprawdzac przy tworzeniu encji payment podczas platnosci
    {
        if ($voucher->getType() !== VoucherTypeEnum::SERVER_DISCOUNT || empty($voucher->getMinimumOrderAmount())) {
            return;
        }


    }

    private function redeemVoucherForUser(Voucher $voucher, User $user): void
    {
        $voucherUsage = (new VoucherUsage())
            ->setUser($user)
            ->setVoucher($voucher);
        $this->voucherUsageRepository->save($voucherUsage);

        $this->logService->logAction($user, LogActionEnum::VOUCHER_REDEEMED, [
            'voucher_code' => $voucher->getCode(),
            'amount' => $voucher->getValue(),
        ]);
        $this->addVoucherBalanceTopup($voucher, $user);

        $voucher->setUsedCount($voucher->getUsedCount() + 1);
        $this->voucherRepository->save($voucher);
    }

    private function addVoucherBalanceTopup(Voucher $voucher, User $user): void
    {
        $updatedUserBalance = (float)$voucher->getValue() + $user->getBalance();
        $user->setBalance($updatedUserBalance);
        $this->userRepository->save($user);
    }
}
