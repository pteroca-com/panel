<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylSubuser extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getUsername(): ?string
    {
        return $this->get('username');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function getImage(): ?string
    {
        return $this->get('image');
    }

    public function is2faEnabled(): ?bool
    {
        return $this->get('2fa_enabled');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getPermissions(): ?array
    {
        return $this->get('permissions');
    }
}
