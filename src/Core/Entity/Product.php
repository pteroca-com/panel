<?php

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: "App\Core\Repository\ProductRepository")]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: "boolean")]
    private bool $isActive = false;

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

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $nodes = [];

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $nest = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $eggs = [];

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[Vich\UploadableField(mapping: 'category_images', fileNameProperty: 'imagePath')]
    private ?File $imageFile = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Gettery i settery dla każdej właściwości

    public function getId(): int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
