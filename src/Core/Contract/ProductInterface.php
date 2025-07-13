<?php

namespace App\Core\Contract;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface ProductInterface
{
    public function getId(): int;
    public function getName(): string;
    public function setName(string $name): self;

    public function getDiskSpace(): int;
    public function setDiskSpace(int $diskSpace): self;

    public function getMemory(): int;
    public function setMemory(int $memory): self;

    public function getIo(): int;
    public function setIo(int $io): self;

    public function getCpu(): int;
    public function setCpu(int $cpu): self;

    public function getThreads(): ?string;
    public function setThreads(?string $threads): self;

    public function getDbCount(): int;
    public function setDbCount(int $dbCount): self;

    public function getSwap(): int;
    public function setSwap(int $swap): self;

    public function getBackups(): int;
    public function setBackups(int $backups): self;

    public function getPorts(): int;
    public function setPorts(int $ports): self;

    public function getNodes(): array;
    public function setNodes(array $nodes): self;

    public function getNest(): ?int;
    public function setNest(?int $nest): self;

    public function getEggs(): array;
    public function setEggs(array $eggs): self;

    public function getEggsConfiguration(): ?string;
    public function setEggsConfiguration(?string $eggsConfiguration): self;

    public function getAllowChangeEgg(): bool;
    public function setAllowChangeEgg(bool $allow): self;

    /**
     * @return Collection<int, ProductPriceInterface>
     */
    public function getPrices(): Collection;

    /**
     * @return Collection<int, ProductPriceInterface>
     */
    public function getStaticPrices(): Collection;

    /**
     * @return Collection<int, ProductPriceInterface>
     */
    public function getDynamicPrices(): Collection;

    /**
     * @param iterable<ProductPriceInterface> $prices
     */
    public function setStaticPrices(iterable $prices): self;

    /**
     * @param iterable<ProductPriceInterface> $prices
     */
    public function setDynamicPrices(iterable $prices): self;

    public function addPrice(ProductPriceInterface $price): self;
    public function removePrice(ProductPriceInterface $price): self;

    public function validatePrices(ExecutionContextInterface $context): void;
}
