<?php

namespace App\Core\Service\Security;

use Psr\Log\LoggerInterface;

/**
 * Registry for custom permissions registered by plugins.
 *
 * Allows plugins to register custom permissions that can be checked via isGranted().
 * Permissions are typically scoped to plugins (e.g., 'PLUGIN_MY_PLUGIN_ADMIN').
 *
 * @example Registering a permission
 * $registry->registerPermission(
 *     'PLUGIN_MY_PLUGIN_ADMIN',
 *     'Admin access to MyPlugin',
 *     ['ROLE_ADMIN']
 * );
 *
 * @example Checking if user has permission
 * if ($security->isGranted('PLUGIN_MY_PLUGIN_ADMIN')) {
 *     // User has permission
 * }
 */
class PermissionRegistry
{
    /**
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     requiredRoles: string[],
     *     customChecker: ?callable
     * }>
     */
    private array $permissions = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Register a custom permission.
     *
     * @param string $name Permission name (e.g., 'PLUGIN_MY_PLUGIN_ADMIN')
     * @param string $description Human-readable description
     * @param string[] $requiredRoles Roles required for this permission
     * @param callable|null $customChecker Optional custom logic for permission checking
     * @return void
     */
    public function registerPermission(
        string $name,
        string $description,
        array $requiredRoles = [],
        ?callable $customChecker = null
    ): void {
        if ($this->hasPermission($name)) {
            $this->logger->warning("Permission '$name' already registered, skipping", [
                'permission' => $name,
            ]);
            return;
        }

        $this->permissions[$name] = [
            'name' => $name,
            'description' => $description,
            'requiredRoles' => $requiredRoles,
            'customChecker' => $customChecker,
        ];

        $this->logger->debug("Registered permission: $name", [
            'permission' => $name,
            'required_roles' => $requiredRoles,
        ]);
    }

    /**
     * Check if permission is registered.
     *
     * @param string $name Permission name
     * @return bool
     */
    public function hasPermission(string $name): bool
    {
        return isset($this->permissions[$name]);
    }

    /**
     * Get permission details.
     *
     * @param string $name Permission name
     * @return array|null Permission details or null if not found
     */
    public function getPermission(string $name): ?array
    {
        return $this->permissions[$name] ?? null;
    }

    /**
     * Get required roles for a permission.
     *
     * @param string $name Permission name
     * @return string[]
     */
    public function getRequiredRoles(string $name): array
    {
        return $this->permissions[$name]['requiredRoles'] ?? [];
    }

    /**
     * Get custom checker for a permission.
     *
     * @param string $name Permission name
     * @return callable|null
     */
    public function getCustomChecker(string $name): ?callable
    {
        return $this->permissions[$name]['customChecker'] ?? null;
    }

    /**
     * Get all registered permissions.
     *
     * @return array<string, array>
     */
    public function getAllPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get permissions grouped by plugin.
     *
     * Assumes permission names follow pattern: PLUGIN_{PLUGIN_NAME}_{PERMISSION}
     *
     * @return array<string, array<string, array>>
     */
    public function getPermissionsByPlugin(): array
    {
        $grouped = [];

        foreach ($this->permissions as $name => $permission) {
            if (preg_match('/^PLUGIN_([A-Z_]+)_/', $name, $matches)) {
                $pluginName = $matches[1];
                $grouped[$pluginName][$name] = $permission;
            } else {
                $grouped['core'][$name] = $permission;
            }
        }

        return $grouped;
    }

    /**
     * Clear all registered permissions.
     *
     * Useful for testing.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->permissions = [];
        $this->logger->debug('Permission registry cleared');
    }

    /**
     * Get count of registered permissions.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->permissions);
    }
}
