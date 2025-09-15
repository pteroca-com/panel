<?php

declare(strict_types=1);

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylDatabase;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylDatabasesInterface
{
    /**
     * @return Collection<PterodactylDatabase>
     */
    public function getDatabases(string $serverId, array $params = []): Collection;

    public function createDatabase(string $serverId, string $database, string $remote): PterodactylDatabase;

    public function rotatePassword(string $serverId, string $databaseId): PterodactylDatabase;

    public function deleteDatabase(string $serverId, string $databaseId): void;
}
