<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use App\Core\Repository\PluginRepository;
use Composer\Semver\Semver;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;

/**
 * Service for resolving and validating plugin dependencies.
 *
 * Provides functionality for:
 * - Validating plugin dependencies (existence, enablement, version compatibility)
 * - Detecting circular dependencies using Depth-First Search
 * - Building dependency trees
 * - Topological sorting for safe loading order
 * - Finding dependents (plugins that depend on a given plugin)
 */
readonly class PluginDependencyResolver
{
    public function __construct(
        private PluginRepository      $pluginRepository,
        private LoggerInterface       $logger,
        private TranslatorInterface   $translator,
    ) {
    }

    /**
     * Validates all dependencies for a plugin.
     *
     * Checks:
     * - Required plugins are installed
     * - Required plugins are enabled
     * - Installed versions satisfy version constraints
     *
     * @param Plugin $plugin The plugin to validate
     * @return array Array of error messages (empty if all dependencies are satisfied)
     */
    public function validateDependencies(Plugin $plugin): array
    {
        $errors = [];
        $requires = $plugin->getRequires();

        foreach ($requires as $requiredPluginName => $versionConstraint) {
            // Check if required plugin exists
            $requiredPlugin = $this->pluginRepository->findByName($requiredPluginName);

            if (!$requiredPlugin) {
                $errors[] = sprintf(
                    "Required plugin '%s' is not installed",
                    $requiredPluginName
                );
                continue;
            }

            // Check if required plugin is enabled
            if (!$requiredPlugin->isEnabled()) {
                $errors[] = sprintf(
                    "Required plugin '%s' is not enabled (current state: %s)",
                    $requiredPluginName,
                    $this->translator->trans($requiredPlugin->getState()->getLabel())
                );
                continue;
            }

            // Validate version compatibility using composer/semver
            try {
                if (!Semver::satisfies($requiredPlugin->getVersion(), $versionConstraint)) {
                    $errors[] = sprintf(
                        "Plugin '%s' requires '%s' %s, but version %s is installed",
                        $plugin->getName(),
                        $requiredPluginName,
                        $versionConstraint,
                        $requiredPlugin->getVersion()
                    );
                }
            } catch (UnexpectedValueException $e) {
                $errors[] = sprintf(
                    "Invalid version constraint '%s' for plugin '%s'",
                    $versionConstraint,
                    $requiredPluginName
                );
                $this->logger->warning('Invalid version constraint', [
                    'plugin' => $plugin->getName(),
                    'required_plugin' => $requiredPluginName,
                    'constraint' => $versionConstraint,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $errors;
    }

    /**
     * Checks if a plugin can be enabled (all dependencies satisfied, no circular deps).
     *
     * @param Plugin $plugin The plugin to check
     * @return bool True if plugin can be enabled
     */
    public function canBeEnabled(Plugin $plugin): bool
    {
        $errors = $this->validateDependencies($plugin);

        if (!empty($errors)) {
            return false;
        }

        if ($this->hasCircularDependency($plugin)) {
            return false;
        }

        return true;
    }

    /**
     * Detects circular dependencies using Depth-First Search algorithm.
     *
     * A circular dependency occurs when Plugin A depends on Plugin B,
     * and Plugin B (directly or transitively) depends on Plugin A.
     *
     * @param Plugin $plugin The plugin to check
     * @param array $visited List of fully processed plugins
     * @param array $stack Current recursion stack for cycle detection
     * @return bool True if circular dependency detected
     */
    public function hasCircularDependency(Plugin $plugin, array $visited = [], array $stack = []): bool
    {
        $pluginName = $plugin->getName();

        // If plugin is in current stack, we found a cycle
        if (in_array($pluginName, $stack, true)) {
            return true;
        }

        // If already fully processed, no need to check again
        if (in_array($pluginName, $visited, true)) {
            return false;
        }

        // Mark as visited
        $visited[] = $pluginName;

        // Add to current path
        $stack[] = $pluginName;

        // Check all dependencies
        $requires = $plugin->getRequires();
        foreach (array_keys($requires) as $requiredPluginName) {
            $requiredPlugin = $this->pluginRepository->findByName($requiredPluginName);

            if ($requiredPlugin && $this->hasCircularDependency($requiredPlugin, $visited, $stack)) {
                return true;
            }
        }

        // Remove from stack (backtrack)
        array_pop($stack);

        return false;
    }

    /**
     * Gets the circular dependency path for error messages.
     *
     * Returns the chain of plugin names that form the circular dependency.
     *
     * @param Plugin $plugin The plugin to check
     * @param array $path Current path being explored
     * @return array|null Array of plugin names forming the cycle, or null if no cycle
     */
    public function getCircularDependencyPath(Plugin $plugin, array $path = []): ?array
    {
        $pluginName = $plugin->getName();

        // If plugin is already in path, we found the cycle
        if (in_array($pluginName, $path, true)) {
            $path[] = $pluginName; // Complete the circle
            return $path;
        }

        $path[] = $pluginName;
        $requires = $plugin->getRequires();

        foreach (array_keys($requires) as $requiredPluginName) {
            $requiredPlugin = $this->pluginRepository->findByName($requiredPluginName);

            if ($requiredPlugin) {
                $result = $this->getCircularDependencyPath($requiredPlugin, $path);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Gets all plugins that depend on the given plugin.
     *
     * @param Plugin $plugin The plugin to check
     * @return Plugin[] Array of dependent plugins
     */
    public function getDependents(Plugin $plugin): array
    {
        return $this->pluginRepository->findDependents($plugin->getName());
    }

    /**
     * Builds a complete dependency tree for a plugin (recursive).
     *
     * Returns a nested array structure representing all dependencies
     * and their sub-dependencies.
     *
     * @param Plugin $plugin The root plugin
     * @param array $visited Plugins already visited (to prevent infinite loops)
     * @return array Dependency tree structure
     */
    public function getDependencyTree(Plugin $plugin, array &$visited = []): array
    {
        $pluginName = $plugin->getName();

        if (in_array($pluginName, $visited, true)) {
            return []; // Avoid infinite loops
        }

        $visited[] = $pluginName;
        $tree = [];
        $requires = $plugin->getRequires();

        foreach ($requires as $requiredPluginName => $versionConstraint) {
            $requiredPlugin = $this->pluginRepository->findByName($requiredPluginName);

            if ($requiredPlugin) {
                $tree[$requiredPluginName] = [
                    'plugin' => $requiredPlugin,
                    'constraint' => $versionConstraint,
                    'children' => $this->getDependencyTree($requiredPlugin, $visited),
                ];
            } else {
                // Plugin not found, but still include in tree for error reporting
                $tree[$requiredPluginName] = [
                    'plugin' => null,
                    'constraint' => $versionConstraint,
                    'children' => [],
                ];
            }
        }

        return $tree;
    }

    /**
     * Returns plugins in topological order (dependencies before dependents).
     *
     * Uses post-order Depth-First Search to determine safe loading order.
     * Plugins with no dependencies appear first, followed by plugins that depend on them.
     *
     * @param Plugin[] $plugins Plugins to sort
     * @return Plugin[] Sorted plugins (dependencies first)
     */
    public function getTopologicalOrder(array $plugins): array
    {
        $sorted = [];
        $visited = [];

        foreach ($plugins as $plugin) {
            $this->topologicalSortVisit($plugin, $visited, $sorted);
        }

        return $sorted;
    }

    /**
     * Recursive helper for topological sort using DFS.
     *
     * @param Plugin $plugin Current plugin being visited
     * @param array $visited Plugins already fully processed
     * @param array $sorted Result array (plugins in topological order)
     */
    private function topologicalSortVisit(Plugin $plugin, array &$visited, array &$sorted): void
    {
        $pluginName = $plugin->getName();

        // Skip if already visited
        if (in_array($pluginName, $visited, true)) {
            return;
        }

        // Mark as visited
        $visited[] = $pluginName;

        // Visit all dependencies first (DFS)
        $requires = $plugin->getRequires();
        foreach (array_keys($requires) as $requiredPluginName) {
            $requiredPlugin = $this->pluginRepository->findByName($requiredPluginName);

            if ($requiredPlugin) {
                $this->topologicalSortVisit($requiredPlugin, $visited, $sorted);
            }
        }

        // Add to sorted list AFTER all dependencies (post-order)
        $sorted[] = $plugin;
    }

    /**
     * Collects all missing or disabled dependencies that need to be enabled.
     *
     * Returns a flat list of all plugins (including transitive dependencies)
     * that need to be enabled for the given plugin to work.
     *
     * @param Plugin $plugin The plugin to analyze
     * @return Plugin[] Array of plugins that need to be enabled
     */
    public function collectMissingDependencies(Plugin $plugin): array
    {
        $toEnable = [];
        $tree = $this->getDependencyTree($plugin);

        $collectRecursive = function ($tree) use (&$collectRecursive, &$toEnable) {
            foreach ($tree as $item) {
                $depPlugin = $item['plugin'];

                if ($depPlugin === null) {
                    continue; // Plugin not installed, skip
                }

                if (!$depPlugin->isEnabled() && !in_array($depPlugin, $toEnable, true)) {
                    $toEnable[] = $depPlugin;
                }

                if (!empty($item['children'])) {
                    $collectRecursive($item['children']);
                }
            }
        };

        $collectRecursive($tree);

        return $toEnable;
    }
}
