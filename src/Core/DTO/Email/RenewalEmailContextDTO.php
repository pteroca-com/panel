<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\ProductInterface;
use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\UserInterface;

readonly class RenewalEmailContextDTO extends EmailContextDTO
{
    public function __construct(
        UserInterface $user,
        string $currency,
        array $serverData,
        array $panelData,
        private ProductInterface $product,
        private ProductPriceInterface $selectedPrice,
    ) {
        parent::__construct($user, $currency, $serverData, $panelData);
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function getSelectedPrice(): ProductPriceInterface
    {
        return $this->selectedPrice;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'product' => $this->product,
            'selectedPrice' => $this->selectedPrice,
        ]);
    }
}
