<?php

namespace App\Core\Contract\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Collection;
use App\Core\DTO\Pterodactyl\Resource;

interface PterodactylNestEggsInterface
{
    /**
     * Get eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     */
    public function getEggs(int $nestId, array $query = []): Collection;

    /**
     * Get a specific egg from a nest.
     *
     * @param int $nestId
     * @param int $eggId
     * @param array $query
     * @return Resource
     */
    public function getEgg(int $nestId, int $eggId, array $query = []): Resource;

    /**
     * Get a paginated collection of eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     */
    public function paginate(int $nestId, array $query = []): Collection;

    /**
     * Get all eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     */
    public function all(int $nestId, array $query = []): Collection;
}
