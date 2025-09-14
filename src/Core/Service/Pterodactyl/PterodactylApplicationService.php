<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\Pterodactyl\PterodactylAdapterInterface;
use App\Core\DTO\Pterodactyl\PterodactylNode;
use App\Core\DTO\Pterodactyl\PterodactylServer;
use App\Core\DTO\Pterodactyl\PterodactylUser;
use App\Core\DTO\Pterodactyl\Resource;

class PterodactylApplicationService
{
    public function __construct(
        private readonly PterodactylAdapterInterface $pterodactylAdapter,
    ) {
    }

    public function allServers(array $parameters = []): array
    {
        return $this->pterodactylAdapter->getServers()->all($parameters);
    }

    public function getServer(string $serverId, array $include = []): PterodactylServer
    {
        return $this->pterodactylAdapter->getServers()->getServer($serverId, $include);
    }

    public function suspendServer(string $serverId): bool
    {
        return $this->pterodactylAdapter->getServers()->suspendServer($serverId);
    }

    public function unsuspendServer(string $serverId): bool
    {
        return $this->pterodactylAdapter->getServers()->unsuspendServer($serverId);
    }

    public function updateServerDetails(string $serverId, array $details): bool
    {
        return $this->pterodactylAdapter->getServers()->updateServerDetails($serverId, $details);
    }

    public function updateServerBuild(string $serverId, array $buildDetails): bool
    {
        return $this->pterodactylAdapter->getServers()->updateServerBuild($serverId, $buildDetails);
    }

    public function updateServerStartup(string $serverId, array $startupDetails): bool
    {
        return $this->pterodactylAdapter->getServers()->updateServerStartup($serverId, $startupDetails);
    }

    public function deleteServer(string $serverId): bool
    {
        return $this->pterodactylAdapter->getServers()->deleteServer($serverId);
    }

    public function createServer(array $details): PterodactylServer
    {
        return $this->pterodactylAdapter->getServers()->createServer($details);
    }

    public function getAllUsers(array $parameters = []): array
    {
        return $this->pterodactylAdapter->getUsers()->getAllUsers($parameters);
    }

    public function getUser(string $userId): ?PterodactylUser
    {
        return $this->pterodactylAdapter->getUsers()->getUser($userId);
    }

    public function updateUser(string $userId, array $details): PterodactylUser
    {
        return $this->pterodactylAdapter->getUsers()->updateUser($userId, $details);
    }

    public function createUser(array $details): PterodactylUser
    {
        return $this->pterodactylAdapter->getUsers()->createUser($details);
    }

    public function deleteUser(string $userId): bool
    {
        return $this->pterodactylAdapter->getUsers()->deleteUser($userId);
    }

    public function getAllNodes(array $parameters = []): array
    {
        return $this->pterodactylAdapter->getNodes()->getAllNodes($parameters);
    }

    public function getNode(string $nodeId): PterodactylNode
    {
        return $this->pterodactylAdapter->getNodes()->getNode($nodeId);
    }

    public function updateNode(string $nodeId, array $details): PterodactylNode
    {
        return $this->pterodactylAdapter->getNodes()->updateNode($nodeId, $details);
    }

    public function createNode(array $details): PterodactylNode
    {
        return $this->pterodactylAdapter->getNodes()->createNode($details);
    }

    public function deleteNode(string $nodeId): bool
    {
        return $this->pterodactylAdapter->getNodes()->deleteNode($nodeId);
    }
}
