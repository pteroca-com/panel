<?php

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to dynamically register plugin entity paths in Doctrine ORM.
 *
 * This runs during container compilation and:
 * - Scans plugins directory for ALL plugins with 'entities' capability (from plugin.json)
 * - Registers their Entity directories in Doctrine's mapping configuration
 * - Uses PSR-4 namespace mapping: Plugins\{PluginName}\Entity
 *
 * NOTE: Registers ALL plugins (not just enabled) because:
 * - Doctrine needs entity metadata at compile-time
 * - Entity classes are lazy-loaded (only loaded when actually used)
 * - Enabled/disabled state is runtime concern, not compile-time
 * - No database connection needed during container compilation
 */
class PluginDoctrineCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if Doctrine ORM is available
        if (!$container->hasDefinition('doctrine.orm.default_metadata_driver')) {
            return;
        }

        $projectDir = $container->getParameter('kernel.project_dir');

        // Scan plugins directory for plugins with 'entities' capability
        $plugins = $this->scanPluginsFromFilesystem($projectDir);

        if (empty($plugins)) {
            return;
        }

        // Get the metadata driver chain
        $chainDriver = $container->getDefinition('doctrine.orm.default_metadata_driver');

        foreach ($plugins as $pluginData) {
            $this->registerPluginEntities($container, $chainDriver, $pluginData, $projectDir);
        }
    }

    /**
     * Register entity paths for a single plugin.
     *
     * @param ContainerBuilder $container
     * @param \Symfony\Component\DependencyInjection\Definition $chainDriver
     * @param array $pluginData
     * @param string $projectDir
     */
    private function registerPluginEntities(
        ContainerBuilder $container,
        $chainDriver,
        array $pluginData,
        string $projectDir
    ): void {
        $pluginName = $pluginData['name'];
        $manifest = $pluginData['manifest'];

        // Check if plugin has 'entities' capability
        if (!isset($manifest['capabilities']) || !in_array('entities', $manifest['capabilities'], true)) {
            return;
        }

        $entityPath = $projectDir . '/plugins/' . $pluginName . '/src/Entity';

        // Check if Entity directory exists
        if (!is_dir($entityPath)) {
            return;
        }

        // Get plugin namespace
        $pluginNamespace = $this->getPluginNamespace($pluginName);
        $entityNamespace = $pluginNamespace . '\\Entity';

        // Create attribute driver for plugin entities
        $driverServiceId = 'doctrine.orm.plugin_' . str_replace('-', '_', $pluginName) . '_metadata_driver';

        $driverDefinition = $container->register($driverServiceId, 'Doctrine\ORM\Mapping\Driver\AttributeDriver');
        $driverDefinition->addArgument([$entityPath]);

        // Add to chain driver
        $chainDriver->addMethodCall('addDriver', [
            $container->getDefinition($driverServiceId),
            $entityNamespace,
        ]);
    }

    /**
     * Scan plugins directory and return plugins with 'entities' capability.
     *
     * Reads plugin.json files from filesystem (no database connection needed).
     * Returns ALL plugins with entities capability, regardless of enabled/disabled state.
     *
     * @param string $projectDir
     * @return array Array of plugin data: ['name' => string, 'manifest' => array]
     */
    private function scanPluginsFromFilesystem(string $projectDir): array
    {
        $pluginsDir = $projectDir . '/plugins';

        // Check if plugins directory exists
        if (!is_dir($pluginsDir)) {
            return [];
        }

        $plugins = [];

        try {
            $directories = scandir($pluginsDir);

            if ($directories === false) {
                return [];
            }

            foreach ($directories as $dir) {
                // Skip . and ..
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                $pluginPath = $pluginsDir . '/' . $dir;

                // Skip if not a directory
                if (!is_dir($pluginPath)) {
                    continue;
                }

                // Check for plugin.json
                $manifestPath = $pluginPath . '/plugin.json';

                if (!file_exists($manifestPath)) {
                    continue;
                }

                // Read and parse manifest
                $manifestContent = file_get_contents($manifestPath);

                if ($manifestContent === false) {
                    error_log("Could not read plugin manifest: {$manifestPath}");
                    continue;
                }

                $manifest = json_decode($manifestContent, true);

                if (!$manifest || json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Invalid JSON in plugin manifest: {$manifestPath}");
                    continue;
                }

                // Check if plugin has 'entities' capability
                $capabilities = $manifest['capabilities'] ?? [];

                if (!in_array('entities', $capabilities, true)) {
                    continue;
                }

                // Add plugin to result
                $plugins[] = [
                    'name' => $dir,
                    'manifest' => $manifest,
                ];
            }

            return $plugins;

        } catch (\Exception $e) {
            error_log("Error scanning plugins directory: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get plugin namespace from plugin name.
     *
     * @param string $pluginName
     * @return string
     */
    private function getPluginNamespace(string $pluginName): string
    {
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\{$className}";
    }
}
