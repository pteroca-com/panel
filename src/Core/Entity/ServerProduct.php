<?php

namespace App\Core\Entity;

use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;
use App\Core\Trait\PricesManagerTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Core\Repository\ServerProductRepository")]
class ServerProduct
{
    use PricesManagerTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\OneToOne(targetEntity: Server::class, inversedBy: 'serverProduct')]
    #[ORM\JoinColumn(nullable: false)]
    private Server $server;

    #[ORM\OneToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $originalProduct;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: "integer")]
    private int $diskSpace;

    #[ORM\Column(type: "integer")]
    private int $memory;

    #[ORM\Column(type: "integer")]
    private int $io = 500;

    #[ORM\Column(type: "integer")]
    private int $cpu;

    #[ORM\Column(type: "integer")]
    private int $dbCount;

    #[ORM\Column(type: "integer")]
    private int $swap;

    #[ORM\Column(type: "integer")]
    private int $backups;

    #[ORM\Column(type: "integer")]
    private int $ports;

    #[ORM\Column(type: "json", nullable: true)]
    private array $nodes = [];

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $nest = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $eggs = [];

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $eggsConfiguration = null;

    #[ORM\Column(type: "boolean")]
    private bool $allowChangeEgg = false;

    #[ORM\OneToMany(targetEntity: ServerProductPrice::class, mappedBy: 'serverProduct', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['server_product:read'])]
    private Collection $prices;

    public function __construct()
    {
        $this->prices = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;
        return $this;
    }

    public function getOriginalProduct(): ?Product
    {
        return $this->originalProduct;
    }

    public function setOriginalProduct(?Product $originalProduct): self
    {
        $this->originalProduct = $originalProduct;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDiskSpace(): int
    {
        return $this->diskSpace;
    }

    public function setDiskSpace(int $diskSpace): self
    {
        $this->diskSpace = $diskSpace;
        return $this;
    }

    public function getMemory(): int
    {
        return $this->memory;
    }

    public function setMemory(int $memory): self
    {
        $this->memory = $memory;
        return $this;
    }

    public function getIo(): int
    {
        return $this->io;
    }

    public function setIo(int $io): self
    {
        $this->io = $io;
        return $this;
    }

    public function getCpu(): int
    {
        return $this->cpu;
    }

    public function setCpu(int $cpu): self
    {
        $this->cpu = $cpu;
        return $this;
    }

    public function getDbCount(): int
    {
        return $this->dbCount;
    }

    public function setDbCount(int $dbCount): self
    {
        $this->dbCount = $dbCount;
        return $this;
    }

    public function getSwap(): int
    {
        return $this->swap;
    }

    public function setSwap(int $swap): self
    {
        $this->swap = $swap;
        return $this;
    }

    public function getBackups(): int
    {
        return $this->backups;
    }

    public function setBackups(int $backups): self
    {
        $this->backups = $backups;
        return $this;
    }

    public function getPorts(): int
    {
        return $this->ports;
    }

    public function setPorts(int $ports): self
    {
        $this->ports = $ports;
        return $this;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;
        return $this;
    }

    public function getNest(): ?int
    {
        return $this->nest;
    }

    public function setNest(?int $nest): self
    {
        $this->nest = $nest;
        return $this;
    }

    public function getEggs(): array
    {
        return $this->eggs;
    }

    public function setEggs(array $eggs): self
    {
        $this->eggs = $eggs;
        return $this;
    }

    public function getEggsConfiguration(): ?string
    {
        return $this->eggsConfiguration;
    }

    public function setEggsConfiguration(?string $eggsConfiguration): self
    {
        $this->eggsConfiguration = $eggsConfiguration;
        return $this;
    }

    public function getAllowChangeEgg(): bool
    {
        return $this->allowChangeEgg;
    }

    public function setAllowChangeEgg(bool $allowChangeEgg): self
    {
        $this->allowChangeEgg = $allowChangeEgg;
        return $this;
    }

    public function getSelectedPrice(): ?ServerProductPrice
    {
        return $this->prices->filter(fn(ServerProductPrice $price) => $price->isSelected())->first() ?: null;
    }

    public function getStaticPrices(): Collection
    {
        return $this->prices->filter(fn(ServerProductPrice $price) => $price->getType() === ProductPriceTypeEnum::STATIC);
    }

    public function getDynamicPrices(): Collection
    {
        return $this->prices->filter(fn(ServerProductPrice $price) => $price->getType() === ProductPriceTypeEnum::ON_DEMAND);
    }

    public function addPrice(ServerProductPrice $price): self
    {
        if (!$this->prices->contains($price)) {
            $this->prices[] = $price;
            $price->setServerProduct($this);
        }
        return $this;
    }

    public function removePrice(ServerProductPrice $price): self
    {
        if ($this->prices->removeElement($price)) {
            // set the owning side to null (unless already changed)
            if ($price->getServerProduct() === $this) {
                $price->setServerProduct(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function syncPrices(iterable $existingPrices, iterable $prices): void
    {
        foreach ($existingPrices as $existingPrice) {
            $found = false;

            foreach ($prices as $submittedPrice) {
                if (
                    $submittedPrice instanceof ServerProductPrice &&
                    $existingPrice->getId() &&
                    $submittedPrice->getId() === $existingPrice->getId()
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->removePrice($existingPrice);
            }
        }

        foreach ($prices as $submittedPrice) {
            if (!$this->prices->contains($submittedPrice)) {
                if ($submittedPrice instanceof ServerProductPrice) {
                    if ($submittedPrice->getType() === ProductPriceTypeEnum::ON_DEMAND) {
                        $submittedPrice->setValue(1);
                        $submittedPrice->setUnit(ProductPriceUnitEnum::MINUTES);
                    }

                    $this->addPrice($submittedPrice);
                }
            }
        }
    }
}
