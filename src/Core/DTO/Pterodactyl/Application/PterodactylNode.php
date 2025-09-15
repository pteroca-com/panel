<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNode extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getLocationId(): ?int
    {
        return $this->get('location_id');
    }

    public function getFqdn(): ?string
    {
        return $this->get('fqdn');
    }

    public function isBehindProxy(): ?bool
    {
        return $this->get('behind_proxy');
    }

    public function isMaintenanceMode(): ?bool
    {
        return $this->get('maintenance_mode');
    }

    public function getDaemonSftp(): ?int
    {
        return $this->get('daemon_sftp');
    }

    public function getDaemonListen(): ?int
    {
        return $this->get('daemon_listen');
    }

    public function isPublic(): ?bool
    {
        return $this->get('public');
    }

    public function getMemory(): ?int
    {
        return $this->get('memory');
    }

    public function getMemoryOverallocate(): ?int
    {
        return $this->get('memory_overallocate');
    }

    public function getDisk(): ?int
    {
        return $this->get('disk');
    }

    public function getDiskOverallocate(): ?int
    {
        return $this->get('disk_overallocate');
    }

    public function getUploadSize(): ?int
    {
        return $this->get('upload_size');
    }

    public function getDaemonBase(): ?string
    {
        return $this->get('daemon_base');
    }

    public function getScheme(): ?string
    {
        return $this->get('scheme');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }
}
