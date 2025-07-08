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

    public function hasServerPermission(ServerPermissionCollection $permissionCollection, string $permission): bool
    {
        return $permissionCollection->hasPermission($permission);
    }

    /**
     * @param string[] $permissions
     */
    public function hasAllServerPermissions(ServerPermissionCollection $permissionCollection, array $permissions): bool
    {
        return $permissionCollection->hasAllPermissions($permissions);
    }

    /**
     * @param string[] $permissions
     */
    public function hasAnyServerPermission(ServerPermissionCollection $permissionCollection, array $permissions): bool
    {
        return $permissionCollection->hasAnyPermission($permissions);
    }

    public function hasServerPermissionInCategory(ServerPermissionCollection $permissionCollection, string $category): bool
    {
        return $permissionCollection->hasPermissionInCategory($category);
    }

    public function hasAllServerPermissionsInCategory(ServerPermissionCollection $permissionCollection, string $category): bool
    {
        return $permissionCollection->hasAllPermissionsInCategory($category);
    }
}
