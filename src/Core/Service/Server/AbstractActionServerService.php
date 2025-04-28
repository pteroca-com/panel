<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\ServerProductPrice;
use App\Core\Entity\User;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Voucher\VoucherPaymentService;

abstract class AbstractActionServerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PterodactylService $pterodactylService,
        private readonly VoucherPaymentService $voucherPaymentService,
    ) {}

    protected function updateUserBalance(
        User $user,
        Product|ServerProduct $product,
        int $priceId,
        ?string $voucherCode = null,
    ): void
    {
        /** @var ProductPrice $price */
        $price = $product->getPrices()->filter(
            fn(ProductPrice|ServerProductPrice $price) => $price->getId() === $priceId
        )->first();

        if (empty($price)) {
            throw new \InvalidArgumentException('Price not found'); // TODO translation
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
                // TODO add log
            }
        }

        if ($balancePaymentAmount > $user->getBalance()) {
            throw new \InvalidArgumentException('Not enough balance'); // TODO translation
        }

        $user->setBalance($user->getBalance() - $balancePaymentAmount);
        $this->userRepository->save($user);
    }

    protected function getPterodactylAccountLogin(User $user): ?string
    {
        return $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId())?->username;
    }
}
