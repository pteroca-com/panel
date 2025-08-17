<?php

namespace App\Core\Service;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Product;
use App\Core\Repository\ServerRepository;

class ConfigurationFeeService
{
    public function __construct(
        private readonly ServerRepository $serverRepository,
    ) {}

    public function shouldApplyConfigurationFee(Product $product, UserInterface $user): bool
    {
        $fee = $product->getConfigurationFee();
        if ($fee === null) {
            return false;
        }

        return !$this->hasUserPurchasedServerBefore($user) && (float) $fee > 0;
    }

    public function getConfigurationFeeAmountForProduct(Product $product, UserInterface $user): float
    {
        if (!$this->shouldApplyConfigurationFee($product, $user)) {
            return 0.0;
        }

        return (float) $product->getConfigurationFee();
    }

    public function calculateTotalWithConfigurationFeeForProduct(float $basePrice, Product $product, UserInterface $user): float
    {
        return $basePrice + $this->getConfigurationFeeAmountForProduct($product, $user);
    }

    private function hasUserPurchasedServerBefore(UserInterface $user): bool
    {
        return $this->serverRepository->createQueryBuilder('s')
            ->select('1')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }
}
