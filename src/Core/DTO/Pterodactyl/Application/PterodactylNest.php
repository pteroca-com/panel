<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNest extends Resource
{
    /**
     * Get the nest ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->get('id');
    }

    /**
     * Get the nest UUID.
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    /**
     * Get the nest author.
     *
     * @return string|null
     */
    public function getAuthor(): ?string
    {
        return $this->get('author');
    }

    /**
     * Get the nest name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get the nest description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->get('description');
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
