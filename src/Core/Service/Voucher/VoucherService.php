<?php

namespace App\Core\Service\Voucher;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Action\Result\RedeemVoucherActionResult;
use App\Core\Entity\Payment;
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
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\SettingService;
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
        private readonly UserVerificationService $userVerificationService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function redeemVoucher(string $code, ?float $orderAmount, UserInterface $user): RedeemVoucherActionResult
    {
        try {
            $this->userVerificationService->validateUserVerification($user);
            $voucher = $this->getValidVoucher($code);
        } catch (Exception $exception) {
            return RedeemVoucherActionResult::failure($exception->getMessage());
        }

        try {
            $voucher = $this->getValidVoucher($code);
            $this->validateNewAccountRequirementIfNeeded($voucher, $user);
            $this->validateOneUsePerUserRequirementIfNeeded($voucher, $user);
            $this->validateMinimumTopupAmountRequirementIfNeeded($voucher, $user);
            $this->validateMinimumOrderAmountRequirementIfNeeded($orderAmount, $voucher);

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
            return RedeemVoucherActionResult::failure(
                $exception->getMessage(),
                $voucher->getType()->value,
                $voucher->getValue(),
            );
        }
    }

    public function getValidVoucher(string $code): Voucher
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

    private function validateNewAccountRequirementIfNeeded(Voucher $voucher, UserInterface $user): void
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

    private function validateOneUsePerUserRequirementIfNeeded(Voucher $voucher, UserInterface $user): void
    {
        if (false === $voucher->isOneUsePerUser()) {
            return;
        }

        if ($this->voucherUsageRepository->hasUsedVoucher($voucher->getCode(), $user->getId())) {
            throw new Exception($this->translator->trans('pteroca.api.voucher.already_used'));
        }
    }

    private function validateMinimumTopupAmountRequirementIfNeeded(Voucher $voucher, UserInterface $user): void
    {
        if (empty($voucher->getMinimumTopupAmount())) {
            return;
        }

        $userPayments = $this->paymentRepository->getUserSuccessfulPayments($user->getId());
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

    private function validateMinimumOrderAmountRequirementIfNeeded(?float $orderAmount, Voucher $voucher): void
    {
        if (
            empty($orderAmount)
            || $voucher->getType() === VoucherTypeEnum::BALANCE_TOPUP
            || empty($voucher->getMinimumOrderAmount())
        ) {
            return;
        }

        if ($voucher->getMinimumOrderAmount() > $orderAmount) {
            $exceptionMessage = $this->translator->trans('pteroca.api.voucher.minimum_order_amount_required', [
               '{{ amount }}' => $voucher->getMinimumOrderAmount(),
            ]);

            throw new Exception($exceptionMessage);
        }
    }

    public function redeemVoucherForUser(Voucher $voucher, UserInterface $user): void
    {
        $voucherUsage = (new VoucherUsage())
            ->setUser($user)
            ->setVoucher($voucher);
        $this->voucherUsageRepository->save($voucherUsage);

        $this->logService->logAction($user, LogActionEnum::VOUCHER_REDEEMED, [
            'voucher_code' => $voucher->getCode(),
            'amount' => $voucher->getValue(),
        ]);

        if ($voucher->getType() === VoucherTypeEnum::BALANCE_TOPUP) {
            $this->addVoucherBalanceTopup($voucher, $user);
        }

        $voucher->setUsedCount($voucher->getUsedCount() + 1);
        $this->voucherRepository->save($voucher);
    }

    private function addVoucherBalanceTopup(Voucher $voucher, UserInterface $user): void
    {
        $updatedUserBalance = (float)$voucher->getValue() + $user->getBalance();
        $user->setBalance($updatedUserBalance);
        $this->userRepository->save($user);
    }
}
