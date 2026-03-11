<?php

namespace App\Core\Entity;

use App\Core\Repository\ThemeRecordRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThemeRecordRepository::class)]
#[ORM\Table(name: 'theme_record')]
#[ORM\HasLifecycleCallbacks]
class ThemeRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $zipHash = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $marketplaceCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
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

    public function getZipHash(): ?string
    {
        return $this->zipHash;
    }

    public function setZipHash(?string $zipHash): self
    {
        $this->zipHash = $zipHash;
        return $this;
    }

    public function getMarketplaceCode(): ?string
    {
        return $this->marketplaceCode;
    }

    public function setMarketplaceCode(?string $marketplaceCode): self
    {
        $this->marketplaceCode = $marketplaceCode;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
