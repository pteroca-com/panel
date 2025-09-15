<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylBackup extends Resource
{
    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getIgnoredFiles(): ?array
    {
        return $this->get('ignored_files');
    }

    public function getSha256Hash(): ?string
    {
        return $this->get('sha256_hash');
    }

    public function getBytes(): ?int
    {
        return $this->get('bytes');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getCompletedAt(): ?string
    {
        return $this->get('completed_at');
    }

    public function isSuccessful(): ?bool
    {
        return $this->get('is_successful');
    }

    public function isLocked(): ?bool
    {
        return $this->get('is_locked');
    }

    public function getChecksum(): ?string
    {
        return $this->get('checksum');
    }
}
