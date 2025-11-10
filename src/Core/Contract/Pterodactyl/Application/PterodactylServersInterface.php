<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Application\PterodactylServer;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylServersInterface
{
    public function all(array $parameters = []): Collection;

    public function paginate(int $page = 1, array $query = []): Collection;

    public function getServer(string $serverId, array $include = []): PterodactylServer;

    public function getServerByExternalId(string $externalId, array $query = []): PterodactylServer;

    public function suspendServer(string $serverId): bool;

    public function unsuspendServer(string $serverId): bool;

    public function updateServerDetails(string $serverId, array $details): PterodactylServer;

    public function updateServerBuild(string $serverId, array $buildDetails): PterodactylServer;

    public function updateServerStartup(string $serverId, array $startupDetails): PterodactylServer;

    public function reinstallServer(string $serverId): bool;

    public function deleteServer(string $serverId): bool;

    public function forceDeleteServer(string $serverId): bool;

    public function createServer(array $details): PterodactylServer;
}
