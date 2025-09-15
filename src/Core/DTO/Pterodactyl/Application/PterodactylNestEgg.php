<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNestEgg extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getNestId(): ?int
    {
        return $this->get('nest');
    }

    public function getAuthor(): ?string
    {
        return $this->get('author');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getDockerImage(): ?string
    {
        return $this->get('docker_image');
    }

    public function getDockerImages(): ?array
    {
        return $this->get('docker_images');
    }

    public function getConfig(): ?array
    {
        return $this->get('config');
    }

    public function getStartup(): ?string
    {
        return $this->get('startup');
    }

    public function getScript(): ?array
    {
        return $this->get('script');
    }

    public function getInstallScript(): ?string
    {
        $script = $this->getScript();
        return $script['install'] ?? null;
    }

    public function getScriptEntry(): ?string
    {
        $script = $this->getScript();
        return $script['entry'] ?? null;
    }

    public function getScriptContainer(): ?string
    {
        $script = $this->getScript();
        return $script['container'] ?? null;
    }

    public function isScriptPrivileged(): ?bool
    {
        $script = $this->getScript();
        return $script['privileged'] ?? null;
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }
}
