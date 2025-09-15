<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Application\PterodactylNest;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylNestsInterface
{
    /**
     * Get a paginated collection of nests.
     *
     * @param array $query
     * @return Collection
     */
    public function paginate(array $query = []): Collection;

    /**
     * Get all nests.
     *
     * @param array $query
     * @return Collection
     */
    public function all(array $query = []): Collection;

    /**
     * Get a nest instance by id.
     *
     * @param int $nestId
     * @param array $query
     * @return PterodactylNest
     */
    public function get(int $nestId, array $query = []): PterodactylNest;

}
