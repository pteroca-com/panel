<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylLocation extends Resource
{
    /**
     * Get the location ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->get('id');
    }

    /**
     * Get the short location code.
     *
     * @return string|null
     */
    public function getShort(): ?string
    {
        return $this->get('short');
    }

    /**
     * Get the long location name.
     *
     * @return string|null
     */
    public function getLong(): ?string
    {
        return $this->get('long');
    }

    /**
     * Get the creation date.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    /**
     * Get the last update date.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }
}
