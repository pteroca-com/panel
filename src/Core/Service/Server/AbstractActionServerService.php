<?php

namespace App\Core\Service\Server;

use App\Core\Contract\ProductInterface;
use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\UserInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\Product\ProductPriceCalculatorService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Voucher\VoucherPaymentService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractActionServerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PterodactylService $pterodactylService,
        private readonly VoucherPaymentService $voucherPaymentService,
        private readonly ProductPriceCalculatorService $productPriceCalculatorService,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {}

    protected function updateUserBalance(
        UserInterface $user,
        ProductInterface $product,
        int $priceId,
        ?string $voucherCode = null,
        ?int $slots = null
    ): void
    {
        $price = $product->getPrices()->filter(
            fn(ProductPriceInterface $price) => $price->getId() === $priceId
        )->first() ?: null;

        if (empty($price)) {
            throw new \InvalidArgumentException($this->translator->trans('pteroca.store.price_not_found'));
        }

        $balancePaymentAmount = $this->productPriceCalculatorService->calculateFinalPrice($price, $slots);
        
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

    protected function getPterodactylAccountLogin(UserInterface $user): ?string
    {
        return $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId())?->username;
    }
}
