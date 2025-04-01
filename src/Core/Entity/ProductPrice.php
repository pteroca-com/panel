<?php

namespace App\Core\Entity;

use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: "App\Core\Repository\ProductPriceRepository")]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    #[ORM\Column(type: 'string', enumType: ProductPriceTypeEnum::class)]
    private ProductPriceTypeEnum $type;

    #[ORM\Column]
    private int $value;

    #[ORM\Column(type: 'string', enumType: ProductPriceUnitEnum::class)]
    private ProductPriceUnitEnum $unit;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $price;

    public function getId(): int
    {
        return $this->id;
    }

    #[Ignore]
    public function getProduct(): ?string
    {
        return $this->product?->getName();
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
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

    public function __toString(): string
    {
        $unit = match ($this->type) {
            ProductPriceTypeEnum::STATIC => 'day(s)',
            ProductPriceTypeEnum::ON_DEMAND => 'minute(s)',
        };

        return sprintf(
            '%d %s: %.2f',
            $this->value,
            $unit,
            $this->price,
        );
    }
}
