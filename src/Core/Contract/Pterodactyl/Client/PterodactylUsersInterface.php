<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylSubuser;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylUsersInterface
{
    /**
     * List all users (subusers) with access to the server.
     *
     * @param string $serverId
     * @return Collection<PterodactylSubuser>
     */
    public function getUsers(string $serverId): Collection;

    /**
     * Create a new subuser for the server.
     *
     * @param string $serverId
     * @param string $email
     * @param array $permissions
     * @return PterodactylSubuser
     */
    public function createUser(string $serverId, string $email, array $permissions): PterodactylSubuser;

    /**
     * Get details of a specific subuser.
     *
     * @param string $serverId
     * @param string $userId
     * @return PterodactylSubuser
     */
    public function getUser(string $serverId, string $userId): PterodactylSubuser;

    /**
     * Update a subuser's permissions.
     *
     * @param string $serverId
     * @param string $userId
     * @param array $permissions
     * @return PterodactylSubuser
     */
    public function updateUserPermissions(string $serverId, string $userId, array $permissions): PterodactylSubuser;

    /**
     * Remove a subuser from the server.
     *
     * @param string $serverId
     * @param string $userId
     * @return void
     */
    public function deleteUser(string $serverId, string $userId): void;
}
