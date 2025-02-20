<?php

namespace App\Core\Entity;

use App\Core\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $context;

    #[ORM\Column(type: "smallint", options: ["default" => 100])]
    private int $hierarchy = 100;

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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getHierarchy(): int
    {
        return $this->hierarchy;
    }

    public function setHierarchy(int $hierarchy): self
    {
        $this->hierarchy = $hierarchy;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
