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

    /**
     * Sprawdza czy kolekcja zawiera określone uprawnienie
     */
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
     * Sprawdza czy kolekcja zawiera wszystkie z podanych uprawnień
     * 
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
     * Sprawdza czy kolekcja zawiera którekolwiek z podanych uprawnień
     * 
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

    /**
     * Sprawdza czy kolekcja zawiera uprawnienia do określonej kategorii
     */
    public function hasPermissionInCategory(string $category): bool
    {
        $groups = ServerPermissionEnum::getPermissionGroups();
        
        if (!isset($groups[$category])) {
            return false;
        }
        
        return $this->hasAnyPermission($groups[$category]);
    }
    
    /**
     * Sprawdza czy kolekcja zawiera wszystkie uprawnienia z określonej kategorii
     */
    public function hasAllPermissionsInCategory(string $category): bool
    {
        $groups = ServerPermissionEnum::getPermissionGroups();
        
        if (!isset($groups[$category])) {
            return false;
        }
        
        return $this->hasAllPermissions($groups[$category]);
    }

    /**
     * Zwraca wszystkie uprawnienia w kolekcji
     * 
     * @return ServerPermissionEnum[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Dodaje uprawnienie do kolekcji
     */
    public function addPermission(ServerPermissionEnum $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
        
        return $this;
    }

    /**
     * Usuwa uprawnienie z kolekcji
     */
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
     * Konwertuje kolekcję na tablicę stringów
     * 
     * @return string[]
     */
    public function toArray(): array
    {
        return array_map(fn(ServerPermissionEnum $permission) => $permission->value, $this->permissions);
    }
}
