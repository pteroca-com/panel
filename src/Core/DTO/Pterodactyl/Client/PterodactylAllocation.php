<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylAllocation extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getIp(): ?string
    {
        return $this->get('ip');
    }

    public function getIpAlias(): ?string
    {
        return $this->get('ip_alias');
    }

    public function getPort(): ?int
    {
        return $this->get('port');
    }

    public function getNotes(): ?string
    {
        return $this->get('notes');
    }

    public function isDefault(): ?bool
    {
        return $this->get('is_default');
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
