<?php

declare(strict_types=1);

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

final class PterodactylDatabase extends Resource
{
    public function getHost(): string
    {
        return $this->host['address'];
    }

    public function getPort(): int
    {
        return $this->host['port'];
    }

    public function getPassword(): ?string
    {
        return $this->relationships['password']['attributes']['password'] ?? null;
    }
}
