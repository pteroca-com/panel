<?php

namespace App\Core\DTO\Pterodactyl\Application;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylServer extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getExternalId(): ?string
    {
        return $this->get('external_id');
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

    public function getUserId(): ?int
    {
        return $this->get('user');
    }

    public function getNodeId(): ?int
    {
        return $this->get('node');
    }

    public function getAllocationId(): ?int
    {
        return $this->get('allocation');
    }

    public function getNestId(): ?int
    {
        return $this->get('nest');
    }

    public function getEggId(): ?int
    {
        return $this->get('egg');
    }

    public function getContainer(): ?array
    {
        return $this->get('container');
    }

    public function getStartupCommand(): ?string
    {
        $container = $this->getContainer();
        return $container['startup_command'] ?? null;
    }

    public function getDockerImage(): ?string
    {
        $container = $this->getContainer();
        return $container['image'] ?? null;
    }

    public function isInstalled(): ?bool
    {
        $container = $this->getContainer();
        return $container['installed'] ?? null;
    }

    public function getEnvironment(): ?array
    {
        $container = $this->getContainer();
        return $container['environment'] ?? null;
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
