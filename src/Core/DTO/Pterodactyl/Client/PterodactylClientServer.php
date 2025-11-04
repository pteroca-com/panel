<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylClientServer extends Resource
{

    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getUuid(): ?string
    {
        return $this->get('uuid');
    }

    public function getIdentifier(): ?string
    {
        return $this->get('identifier');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function isSuspended(): ?bool
    {
        return $this->get('suspended');
    }

    public function getLimits(): ?array
    {
        return $this->get('limits');
    }

    public function getMemoryLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['memory'] ?? null;
    }

    public function getSwapLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['swap'] ?? null;
    }

    public function getDiskLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['disk'] ?? null;
    }

    public function getIoLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['io'] ?? null;
    }

    public function getCpuLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['cpu'] ?? null;
    }

    public function getThreadsLimit(): ?int
    {
        $limits = $this->getLimits();
        return $limits['threads'] ?? null;
    }

    public function isOomDisabled(): ?bool
    {
        $limits = $this->getLimits();
        return $limits['oom_disabled'] ?? null;
    }

    public function getFeatureLimits(): ?array
    {
        return $this->get('feature_limits');
    }

    public function getDatabasesLimit(): ?int
    {
        $limits = $this->getFeatureLimits();
        return $limits['databases'] ?? null;
    }

    public function getAllocationsLimit(): ?int
    {
        $limits = $this->getFeatureLimits();
        return $limits['allocations'] ?? null;
    }

    public function getBackupsLimit(): ?int
    {
        $limits = $this->getFeatureLimits();
        return $limits['backups'] ?? null;
    }

    public function getNode(): ?string
    {
        return $this->get('node');
    }

    public function getSftpDetails(): ?array
    {
        return $this->get('sftp_details');
    }

    public function getSftpIp(): ?string
    {
        $sftpDetails = $this->getSftpDetails();
        return $sftpDetails['ip'] ?? null;
    }

    public function getSftpPort(): ?int
    {
        $sftpDetails = $this->getSftpDetails();
        return $sftpDetails['port'] ?? null;
    }

    public function isInstalling(): ?bool
    {
        return $this->get('is_installing');
    }

    public function isTransferring(): ?bool
    {
        return $this->get('is_transferring');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    public function getResourceUsage(): ?array
    {
        return $this->get('utilization');
    }

    public function getCurrentState(): ?string
    {
        return $this->get('current_state');
    }

    public function getInvocation(): ?string
    {
        return $this->get('invocation');
    }

    public function getDockerImage(): ?string
    {
        return $this->get('docker_image');
    }

    public function getEggFeatures(): ?array
    {
        return $this->get('egg_features');
    }
}
