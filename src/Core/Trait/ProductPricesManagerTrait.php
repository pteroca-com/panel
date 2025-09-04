<?php

namespace App\Core\Trait;

use App\Core\Contract\ProductPriceInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

trait ProductPricesManagerTrait
{
    public function getPrices(): Collection
    {
        return $this->prices->filter(function (ProductPriceInterface $price) {
            return $price->getDeletedAt() === null;
        });
    }

    public function setStaticPrices(iterable $incomingPrices): self
    {
        $this->syncPrices(
            $this->getStaticPrices(),
            $incomingPrices
        );

        return $this;
    }

    public function setDynamicPrices(iterable $prices): self
    {
        $this->syncPrices(
            $this->getDynamicPrices(),
            $prices
        );

        return $this;
    }

    public function findPriceById(int $priceId): ?ProductPriceInterface
    {
        return $this->prices->filter(
            fn(ProductPriceInterface $price) => $price->getId() === $priceId && !$price->getDeletedAt()
        )->first() ?: null;
    }

    #[Assert\Callback]
    public function validatePrices(ExecutionContextInterface $context): void
    {
        if (count($this->getPrices()) === 0) {
            $context->buildViolation('pteroca.crud.product.at_least_one_price_required')
                ->setTranslationDomain('messages')
                ->atPath('prices')
                ->addViolation();
        }
    }
}
