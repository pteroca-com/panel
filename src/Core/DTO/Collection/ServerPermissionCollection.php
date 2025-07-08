<?php

namespace App\Core\DTO\Collection;

use App\Core\Enum\ServerPermissionEnum;

class ServerPermissionCollection
{
    /**
     * @var ServerPermissionEnum[]
     */
    private array $permissions = [];

    /**
     * @param ServerPermissionEnum[] $permissions
     */
    public function __construct(array $permissions = [])
    {
        $this->permissions = $permissions;
    }

    public function hasPermission(ServerPermissionEnum|string $permission): bool
    {
        if (is_string($permission)) {
            try {
                $permission = ServerPermissionEnum::from($permission);
            } catch (\ValueError $e) {
                return false;
            }
        }
        
        return in_array($permission, $this->permissions, true);
    }

    /**
     * @param array<ServerPermissionEnum|string> $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * @param array<ServerPermissionEnum|string> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    public function hasPermissionInCategory(string $category): bool
    {
        $groups = ServerPermissionEnum::getPermissionGroups();
        
        if (!isset($groups[$category])) {
            return false;
        }
        
        return $this->hasAnyPermission($groups[$category]);
    }
    
    public function hasAllPermissionsInCategory(string $category): bool
    {
        $groups = ServerPermissionEnum::getPermissionGroups();
        
        if (!isset($groups[$category])) {
            return false;
        }
        
        return $this->hasAllPermissions($groups[$category]);
    }

    /**
     * @return ServerPermissionEnum[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(ServerPermissionEnum $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
        
        return $this;
    }

    public function removePermission(ServerPermissionEnum $permission): self
    {
        $key = array_search($permission, $this->permissions, true);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
        }
        
        return $this;
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return array_map(fn(ServerPermissionEnum $permission) => $permission->value, $this->permissions);
    }
}
