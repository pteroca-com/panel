<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylApiKey extends Resource
{
    public function getIdentifier(): ?string
    {
        return $this->get('identifier');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getAllowedIps(): ?array
    {
        return $this->get('allowed_ips');
    }

    public function getLastUsedAt(): ?string
    {
        return $this->get('last_used_at');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getSecretToken(): ?string
    {
        return $this->get('secret_token');
    }
}
