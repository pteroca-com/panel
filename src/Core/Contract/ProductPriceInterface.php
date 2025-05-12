<?php

namespace App\Core\Contract;

use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;

interface ProductPriceInterface
{
    public function getId(): int;

    public function getType(): ProductPriceTypeEnum;
    public function setType(ProductPriceTypeEnum $type): self;

    public function getValue(): int;
    public function setValue(int $value): self;

    public function getUnit(): ProductPriceUnitEnum;
    public function setUnit(ProductPriceUnitEnum $unit): self;

    public function getPrice(): float;
    public function setPrice(float $price): self;

    public function getDeletedAt(): ?\DateTime;
    public function setDeletedAt(?\DateTime $deletedAt): self;

    public function __toString(): string;
}