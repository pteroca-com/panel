<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\ProductPriceInterface;

readonly class PriceCalculationDTO
{
    public function __construct(
        private ProductPriceInterface $originalPrice,
        private float $basePrice,
        private float $finalPrice,
        private string $pricingType,
        private string $formattedDescription,
        private ?int $slots = null,
    ) {}

    public function getOriginalPrice(): ProductPriceInterface
    {
        return $this->originalPrice;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function getPricingType(): string
    {
        return $this->pricingType;
    }

    public function getFormattedDescription(): string
    {
        return $this->formattedDescription;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }

    public function toArray(): array
    {
        return [
            'originalPrice' => $this->originalPrice,
            'basePrice' => $this->basePrice,
            'finalPrice' => $this->finalPrice,
            'pricingType' => $this->pricingType,
            'formattedDescription' => $this->formattedDescription,
            'slots' => $this->slots,
        ];
    }
}
