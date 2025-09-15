<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylUser extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getExternalId(): ?string
    {
        return $this->get('external_id');
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

    public function getFirstName(): ?string
    {
        return $this->get('first_name');
    }

    public function getLastName(): ?string
    {
        return $this->get('last_name');
    }

    public function getLanguage(): ?string
    {
        return $this->get('language');
    }

    public function isRootAdmin(): ?bool
    {
        return $this->get('root_admin');
    }

    public function is2faEnabled(): ?bool
    {
        return $this->get('2fa');
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
