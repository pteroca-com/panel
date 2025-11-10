<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylApiKey extends Resource
{
    public function getIdentifier(): ?string
    {
        return $this->get('identifier');
    }

    public function getSecretToken(): ?string
    {
        return $this->meta['secret_token'] ?? null;
    }

    public function getFullApiKey(): string
    {
        return sprintf('%s%s', $this->getIdentifier(), $this->getSecretToken());
    }
}
