<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Application\PterodactylLocation;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylLocationsInterface
{
    /**
     * Get a paginated collection of locations.
     *
     * @param array $query
     * @return Collection
     */
    public function paginate(array $query = []): Collection;

    /**
     * Get all locations.
     *
     * @param array $query
     * @return Collection
     */
    public function all(array $query = []): Collection;

    /**
     * Get a location instance by id.
     *
     * @param int $locationId
     * @param array $query
     * @return PterodactylLocation
     */
    public function get(int $locationId, array $query = []): PterodactylLocation;

    /**
     * Create a new location.
     *
     * @param array $data
     * @return PterodactylLocation
     */
    public function create(array $data): PterodactylLocation;

    /**
     * Update a specified location.
     *
     * @param int $locationId
     * @param array $data
     * @return PterodactylLocation
     */
    public function update(int $locationId, array $data): PterodactylLocation;

    /**
     * Delete the given location.
     *
     * @param int $locationId
     * @return void
     */
    public function delete(int $locationId): void;
}
