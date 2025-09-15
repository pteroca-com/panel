<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Application\PterodactylNode;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylNodesInterface
{
    public function getAllNodes(array $parameters = []): Collection;

    public function paginateNodes(int $page = 1, array $parameters = []): Collection;

    public function getNode(string $nodeId): PterodactylNode;

    public function getNodeConfiguration(string $nodeId): array;

    public function updateNode(string $nodeId, array $details): PterodactylNode;

    public function createNode(array $details): PterodactylNode;

    public function deleteNode(string $nodeId): bool;

    public function getAllocations(string $nodeId, array $parameters = []): Collection;

    public function createAllocations(string $nodeId, array $data): Collection;

    public function deleteAllocation(string $nodeId, string $allocationId): bool;
}
