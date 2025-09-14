<?php

namespace App\Core\Contract\Pterodactyl;

use App\Core\DTO\Pterodactyl\PterodactylServer;

interface PterodactylServersInterface
{
    public function all(array $parameters = []): array;

    public function paginate(int $page = 1, array $query = []): array;

    public function getServer(string $serverId, array $include = []): PterodactylServer;

    public function getServerByExternalId(string $externalId, array $query = []): PterodactylServer;

    public function suspendServer(string $serverId): bool;

    public function unsuspendServer(string $serverId): bool;

    public function updateServerDetails(string $serverId, array $details): bool;

    public function updateServerBuild(string $serverId, array $buildDetails): bool;

    public function updateServerStartup(string $serverId, array $startupDetails): bool;

    public function reinstallServer(string $serverId): bool;

    public function deleteServer(string $serverId): bool;

    public function forceDeleteServer(string $serverId): bool;

    public function createServer(array $details): PterodactylServer;
}
