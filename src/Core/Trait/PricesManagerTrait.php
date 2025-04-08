<?php

namespace App\Core\Trait;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

trait PricesManagerTrait
{
    public function getPrices(): Collection
    {
        return $this->prices;
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