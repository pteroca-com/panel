<?php

namespace App\Core\Security;

use App\Core\Enum\ServerPermissionEnum;

class ServerPermissionManager
{
    /**
     * @var ServerPermissionEnum[]
     */
    private array $permissions = [];

    /**
     * Tworzy nowy obiekt zarządzający uprawnieniami na podstawie tablicy stringów uprawnień
     */
    public function __construct(array $permissionsArray = [])
    {
        $this->permissions = ServerPermissionEnum::fromArray($permissionsArray);
    }

    /**
     * Sprawdza czy użytkownik ma określone uprawnienie (wersja przyjmująca enum)
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
     * Sprawdza czy użytkownik ma wszystkie z podanych uprawnień
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
     * Sprawdza czy użytkownik ma którekolwiek z podanych uprawnień
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
     * Zwraca wszystkie uprawnienia użytkownika
     * 
     * @return ServerPermissionEnum[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
    
    /**
     * Sprawdza czy użytkownik ma uprawnienia do określonej kategorii
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
     * Sprawdza czy użytkownik ma wszystkie uprawnienia z określonej kategorii
     */
    public function hasAllPermissionsInCategory(string $category): bool
    {
        $groups = ServerPermissionEnum::getPermissionGroups();
        
        if (!isset($groups[$category])) {
            return false;
        }
        
        return $this->hasAllPermissions($groups[$category]);
    }
}
