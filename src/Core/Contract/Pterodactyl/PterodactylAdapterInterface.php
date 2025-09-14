<?php

namespace App\Core\Contract\Pterodactyl;

interface PterodactylAdapterInterface
{
    /**
     * Get servers management interface
     *
     * @return PterodactylServersInterface
     */
    public function getServers(): PterodactylServersInterface;

    /**
     * Get users management interface
     *
     * @return PterodactylUsersInterface
     */
    public function getUsers(): PterodactylUsersInterface;

    public function getNodes(): PterodactylNodesInterface;
}
