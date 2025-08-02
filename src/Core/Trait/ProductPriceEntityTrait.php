<?php

namespace App\Core\Trait;

use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;
use Doctrine\ORM\Mapping as ORM;

trait ProductPriceEntityTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: 'string', enumType: ProductPriceTypeEnum::class)]
    private ProductPriceTypeEnum $type;

    #[ORM\Column]
    private int $value;

    #[ORM\Column(type: 'string', enumType: ProductPriceUnitEnum::class)]
    private ProductPriceUnitEnum $unit;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: "boolean")]
    private bool $hasFreeTrial = false;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $freeTrialValue = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: ProductPriceUnitEnum::class)]
    private ?ProductPriceUnitEnum $freeTrialUnit = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $deletedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ProductPriceTypeEnum
    {
        return $this->type;
    }

    public function setType(ProductPriceTypeEnum $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnit(): ProductPriceUnitEnum
    {
        return $this->unit;
    }

    public function setUnit(ProductPriceUnitEnum $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function hasFreeTrial(): bool
    {
        return $this->hasFreeTrial;
    }

    public function setHasFreeTrial(bool $hasFreeTrial): self
    {
        $this->hasFreeTrial = $hasFreeTrial;
        return $this;
    }

    public function getFreeTrialValue(): ?int
    {
        return $this->freeTrialValue;
    }

    public function setFreeTrialValue(?int $value): self
    {
        $this->freeTrialValue = $value;
        return $this;
    }

    public function getFreeTrialUnit(): ?ProductPriceUnitEnum
    {
        return $this->freeTrialUnit;
    }

    public function setFreeTrialUnit(?ProductPriceUnitEnum $unit): self
    {
        $this->freeTrialUnit = $unit;
        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function __toString(): string
    {
        $unit = match ($this->type) {
            ProductPriceTypeEnum::STATIC => 'day(s)',
            ProductPriceTypeEnum::ON_DEMAND => 'minute(s)',
        };

        $trial = '';
        if ($this->hasFreeTrial && $this->freeTrialValue !== null && $this->freeTrialUnit !== null) {
            $trial = sprintf(
                ' (Free trial: %d %s)',
                $this->freeTrialValue,
                $this->freeTrialUnit->value
            );
        }

        return sprintf(
            '%d %s: %.2f%s',
            $this->value,
            $unit,
            $this->price,
            $trial
        );
    }
}
