<?php

namespace App\Core\Twig;

use App\Core\Security\ServerPermissionManager;
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
    public function hasServerPermission(ServerPermissionManager $permissionManager, string $permission): bool
    {
        return $permissionManager->hasPermission($permission);
    }

    /**
     * Sprawdza czy użytkownik ma wszystkie z podanych uprawnień
     * 
     * @param string[] $permissions
     */
    public function hasAllServerPermissions(ServerPermissionManager $permissionManager, array $permissions): bool
    {
        return $permissionManager->hasAllPermissions($permissions);
    }

    /**
     * Sprawdza czy użytkownik ma którekolwiek z podanych uprawnień
     * 
     * @param string[] $permissions
     */
    public function hasAnyServerPermission(ServerPermissionManager $permissionManager, array $permissions): bool
    {
        return $permissionManager->hasAnyPermission($permissions);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do określonej kategorii
     */
    public function hasServerPermissionInCategory(ServerPermissionManager $permissionManager, string $category): bool
    {
        return $permissionManager->hasPermissionInCategory($category);
    }

    /**
     * Sprawdza czy użytkownik ma wszystkie uprawnienia z określonej kategorii
     */
    public function hasAllServerPermissionsInCategory(ServerPermissionManager $permissionManager, string $category): bool
    {
        return $permissionManager->hasAllPermissionsInCategory($category);
    }
}
