<?php

namespace App\Core\Service\Product;

use App\Core\Contract\ProductPriceInterface;
use App\Core\Enum\ProductPriceTypeEnum;

class ProductPriceCalculatorService
{
    public function calculateFinalPrice(ProductPriceInterface $price, ?int $slots = null): float
    {
        if ($price->getType()->value === ProductPriceTypeEnum::SLOT->value && $slots !== null && $slots > 0) {
            return $price->getPrice() * $slots;
        }
        
        return $price->getPrice();
    }
}
