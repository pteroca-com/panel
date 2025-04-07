<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\ServerProductPrice;
use App\Core\Entity\User;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

abstract class AbstractActionServerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PterodactylService $pterodactylService,
    ) {}

    protected function updateUserBalance(User $user, Product|ServerProduct $product, int $priceId): void
    {
        /** @var ProductPrice $price */
        $price = $product->getPrices()->filter(
            fn(ProductPrice|ServerProductPrice $price) => $price->getId() === $priceId
        )->first();

        if (empty($price)) {
            throw new \InvalidArgumentException('Price not found');
        }

        $user->setBalance($user->getBalance() - $price->getPrice());
        $this->userRepository->save($user);
    }

    protected function getPterodactylAccountLogin(User $user): ?string
    {
        return $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId())?->username;
    }
}
