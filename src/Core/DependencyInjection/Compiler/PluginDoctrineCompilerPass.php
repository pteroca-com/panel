<?php

namespace App\Core\DependencyInjection\Compiler;

use App\Core\Trait\PluginDirectoryScannerTrait;
use Exception;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Compiler pass to dynamically register plugin entity paths in Doctrine ORM.
 *
 * This runs during container compilation and:
 * - Scans plugins directory for ALL plugins with 'entities' capability (from plugin.json)
 * - Registers their Entity directories in Doctrine's mapping configuration
 * - Uses PSR-4 namespace mapping: Plugins\{PluginName}\Entity
 */
class PluginDoctrineCompilerPass implements CompilerPassInterface
{
    use PluginDirectoryScannerTrait;
    public function process(ContainerBuilder $container): void
    {
        // Check if Doctrine ORM is available
        if (!$container->hasDefinition('doctrine.orm.default_metadata_driver')) {
            return;
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        $pluginsDir = $projectDir . '/plugins';

        // Scan plugins directory for plugins with 'entities' capability
        $plugins = $this->scanPluginDirectory($pluginsDir);

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
     * @param Definition $chainDriver
     * @param array $pluginData
     * @param string $projectDir
     */
    private function registerPluginEntities(
        ContainerBuilder $container,
        Definition       $chainDriver,
        array            $pluginData,
        string           $projectDir
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
     * Get plugin namespace from plugin name.
     *
     * @param string $pluginName
     * @return string
     */
    private function getPluginNamespace(string $pluginName): string
    {
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\$className";
    }
}
