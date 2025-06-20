<?php

namespace App\Core\Twig;

use App\Core\DTO\Collection\ServerPermissionCollection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ServerPermissionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasServerPermission', [$this, 'hasServerPermission']),
            new TwigFunction('hasAllServerPermissions', [$this, 'hasAllServerPermissions']),
            new TwigFunction('hasAnyServerPermission', [$this, 'hasAnyServerPermission']),
            new TwigFunction('hasServerPermissionInCategory', [$this, 'hasServerPermissionInCategory']),
            new TwigFunction('hasAllServerPermissionsInCategory', [$this, 'hasAllServerPermissionsInCategory']),
        ];
    }

    /**
     * Sprawdza czy użytkownik ma określone uprawnienie
     */
    public function hasServerPermission(ServerPermissionCollection $permissionCollection, string $permission): bool
    {
        return $permissionCollection->hasPermission($permission);
    }

    /**
     * Sprawdza czy użytkownik ma wszystkie z podanych uprawnień
     * 
     * @param string[] $permissions
     */
    public function hasAllServerPermissions(ServerPermissionCollection $permissionCollection, array $permissions): bool
    {
        return $permissionCollection->hasAllPermissions($permissions);
    }

    /**
     * Sprawdza czy użytkownik ma którekolwiek z podanych uprawnień
     * 
     * @param string[] $permissions
     */
    public function hasAnyServerPermission(ServerPermissionCollection $permissionCollection, array $permissions): bool
    {
        return $permissionCollection->hasAnyPermission($permissions);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do określonej kategorii
     */
    public function hasServerPermissionInCategory(ServerPermissionCollection $permissionCollection, string $category): bool
    {
        return $permissionCollection->hasPermissionInCategory($category);
    }

    /**
     * Sprawdza czy użytkownik ma wszystkie uprawnienia z określonej kategorii
     */
    public function hasAllServerPermissionsInCategory(ServerPermissionCollection $permissionCollection, string $category): bool
    {
        return $permissionCollection->hasAllPermissionsInCategory($category);
    }
}
