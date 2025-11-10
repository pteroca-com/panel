<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylClientServer;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylServersInterface
{
    public function getServers(array $parameters = []): Collection;

    public function getServer(string $serverId, array $include = []): PterodactylClientServer;

    public function getServerUtilization(string $serverId): array;

    public function getServerResources(string $serverId): array;

    public function sendPowerSignal(string $serverId, string $signal): bool;

    public function sendCommand(string $serverId, string $command): bool;

    public function getWebSocketToken(string $serverId): array;

    public function reinstallServer(string $serverId): bool;

    public function updateServerName(string $serverId, string $name, ?string $description = null): PterodactylClientServer;

    public function updateServerDockerImage(string $serverId, string $dockerImage): PterodactylClientServer;

    public function updateServerStartup(string $serverId, array $startupData): PterodactylClientServer;

    public function updateServerStartupVariable(string $serverId, string $key, string $value): array;

    public function getServerStartup(string $serverId): array;

    public function getServerActivity(string $serverId, array $parameters = []): Collection;

    public function getPermissions(): array;

    public function getServerPermissions(string $serverId): array;
}
