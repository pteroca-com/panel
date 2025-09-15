<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylAccount extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function getUsername(): ?string
    {
        return $this->get('username');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getLanguage(): ?string
    {
        return $this->get('language');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    public function getGravatar(): ?string
    {
        return $this->get('gravatar');
    }

    public function hasAdminAccess(): ?bool
    {
        return $this->get('admin_access');
    }

    public function isRootAdmin(): ?bool
    {
        return $this->get('root_admin');
    }

    public function isTwoFactorEnabled(): ?bool
    {
        return $this->get('two_factor_enabled');
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->get('2fa_secret');
    }
}
