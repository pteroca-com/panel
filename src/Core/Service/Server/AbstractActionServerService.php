<?php

namespace App\Core\Service\Server;

use App\Core\Contract\ProductInterface;
use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\UserInterface;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Voucher\VoucherPaymentService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractActionServerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ServerRepository $serverRepository,
        private readonly PterodactylService $pterodactylService,
        private readonly VoucherPaymentService $voucherPaymentService,
        private readonly TranslatorInterface $translator,
        protected readonly LoggerInterface $logger,
    ) {}

    protected function updateUserBalance(
        UserInterface $user,
        ProductInterface $product,
        int $priceId,
        ?string $voucherCode = null,
    ): void
    {
        $price = $product->getPrices()->filter(
            fn(ProductPriceInterface $price) => $price->getId() === $priceId
        )->first() ?: null;

        if (empty($price)) {
            throw new \InvalidArgumentException($this->translator->trans('pteroca.store.price_not_found'));
        }

        // Check if user qualifies for free trial
        $isFreeTrial = $this->checkUserFreeTrialEligibility($user, $price);
        
        if ($isFreeTrial) {
            // No balance deduction for free trial
            $this->logger->info('Free trial applied for user', [
                'user' => $user->getId(),
                'price' => $price->getId(),
                'freeTrialValue' => $price->getFreeTrialValue(),
                'freeTrialUnit' => $price->getFreeTrialUnit()?->value,
            ]);
            return;
        }

        $balancePaymentAmount = $price->getPrice();
        if (!empty($voucherCode)) {
            try {
                $balancePaymentAmount = $this->voucherPaymentService->redeemPaymentVoucher(
                    $balancePaymentAmount,
                    $voucherCode,
                    $user,
                );
            } catch (\Exception $exception) {
                $this->logger->error('Failed to redeem payment voucher', [
                    'user' => $user->getId(),
                    'voucherCode' => $voucherCode,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ($balancePaymentAmount > $user->getBalance()) {
            throw new \InvalidArgumentException($this->translator->trans('pteroca.store.not_enough_funds'));
        }

        $user->setBalance($user->getBalance() - $balancePaymentAmount);
        $this->userRepository->save($user);
    }

    /**
     * Check if user is eligible for free trial
     */
    private function checkUserFreeTrialEligibility(UserInterface $user, ProductPriceInterface $price): bool
    {
        // No free trial if not configured
        if (!$price->hasFreeTrial() || empty($price->getFreeTrialValue())) {
            return false;
        }

        // Check if user already used free trial for any product
        // You can customize this logic based on your requirements:
        // - Per product free trial
        // - Global free trial (once per user)
        // - Time-based restrictions, etc.
        
        // Simple implementation: users who never purchased any server get free trial
        $hasEverPurchased = $this->serverRepository->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        
        return empty($hasEverPurchased);
    }

    protected function getPterodactylAccountLogin(UserInterface $user): ?string
    {
        return $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId())?->username;
    }
}
