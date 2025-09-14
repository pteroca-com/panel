<?php

namespace App\Core\Contract\Pterodactyl;

use App\Core\DTO\Pterodactyl\PterodactylNode;

interface PterodactylNodesInterface
{
    public function getAllNodes(array $parameters = []): array;

    public function getNode(string $nodeId): PterodactylNode;

    public function updateNode(string $nodeId, array $details): PterodactylNode;

    public function createNode(array $details): PterodactylNode;

    public function deleteNode(string $nodeId): bool;
}