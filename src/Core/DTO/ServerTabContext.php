<?php

namespace App\Core\DTO;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;

class ServerTabContext
{
    public function __construct(
        public readonly Server $server,
        public readonly object $serverData, // ServerData DTO
        public readonly UserInterface $user,
        public readonly bool $isAdminView,
        public readonly bool $isOwner,
    ) {}

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdminView) {
            return true; // Admins have all permissions
        }

        if (empty($this->serverData->serverPermissions)) {
            return false;
        }

        $permissions = $this->serverData->serverPermissions->toArray();
        return in_array($permission, $permissions, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function getProductFeature(string $feature): mixed
    {
        $product = $this->server->getServerProduct();

        return match($feature) {
            'dbCount' => $product->getDbCount(),
            'backups' => $product->getBackups(),
            'schedules' => $product->getSchedules(),
            default => null,
        };
    }

    public function hasConfigurableStartup(): bool
    {
        return !empty($this->serverData->hasConfigurableOptions)
            || !empty($this->serverData->hasConfigurableVariables);
    }
}
