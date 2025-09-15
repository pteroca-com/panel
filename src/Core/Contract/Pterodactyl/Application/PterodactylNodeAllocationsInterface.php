<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylNodeAllocationsInterface
{
    /**
     * Get a paginated collection of allocations for a specific node.
     *
     * @param int $nodeId
     * @param array $query
     * @return Collection
     */
    public function paginate(int $nodeId, array $query = []): Collection;

    /**
     * Get all allocations for a specific node.
     *
     * @param int $nodeId
     * @param array $query
     * @return Collection
     */
    public function all(int $nodeId, array $query = []): Collection;

    /**
     * Create new allocations for a node.
     *
     * @param int $nodeId
     * @param array $data
     * @return Collection
     */
    public function create(int $nodeId, array $data): Collection;

    /**
     * Delete a specific allocation from a node.
     *
     * @param int $nodeId
     * @param int $allocationId
     * @return void
     */
    public function delete(int $nodeId, int $allocationId): void;
}
