<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNodeAllocation extends Resource
{
    /**
     * Get the allocation ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->get('id');
    }

    /**
     * Get the IP address.
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->get('ip');
    }

    /**
     * Get the IP alias.
     *
     * @return string|null
     */
    public function getIpAlias(): ?string
    {
        return $this->get('ip_alias');
    }

    /**
     * Get the port number.
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->get('port');
    }

    /**
     * Get allocation notes.
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->get('notes');
    }

    /**
     * Check if allocation is assigned to a server.
     *
     * @return bool
     */
    public function isAssigned(): bool
    {
        return (bool) $this->get('assigned', false);
    }
}
