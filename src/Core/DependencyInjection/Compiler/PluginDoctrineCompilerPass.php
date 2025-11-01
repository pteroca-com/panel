<?php

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to dynamically register plugin entity paths in Doctrine ORM.
 *
 * This runs during container compilation and:
 * - Queries database for ENABLED plugins with 'entities' capability
 * - Registers their Entity directories in Doctrine's mapping configuration
 * - Uses PSR-4 namespace mapping: Plugins\{PluginName}\Entity
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

        // Get enabled plugins from database
        $enabledPlugins = $this->getEnabledPlugins($container);

        if (empty($enabledPlugins)) {
            return;
        }

        // Get the metadata driver chain
        $chainDriver = $container->getDefinition('doctrine.orm.default_metadata_driver');

        foreach ($enabledPlugins as $pluginData) {
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
     * Get enabled plugins from database.
     *
     * @param ContainerBuilder $container
     * @return array
     */
    private function getEnabledPlugins(ContainerBuilder $container): array
    {
        try {
            // Get database URL from environment
            $databaseUrl = getenv('DATABASE_URL');

            if (empty($databaseUrl)) {
                if ($container->hasParameter('database_url')) {
                    $databaseUrl = $container->getParameter('database_url');
                } else {
                    return [];
                }
            }

            // Create temporary PDO connection (supports MySQL and PostgreSQL)
            $pdo = $this->createDatabaseConnection($databaseUrl);

            if (!$pdo) {
                return [];
            }

            // Query for enabled plugins
            $stmt = $pdo->query("
                SELECT name, manifest
                FROM plugin
                WHERE state = 'enabled'
                ORDER BY name ASC
            ");

            if (!$stmt) {
                return [];
            }

            $plugins = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $plugins[] = [
                    'name' => $row['name'],
                    'manifest' => json_decode($row['manifest'], true),
                ];
            }

            return $plugins;

        } catch (\Exception $e) {
            // Silently fail - plugin table might not exist yet
            error_log("Could not load plugin entities during compilation: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Create database connection from DATABASE_URL.
     *
     * Supports both MySQL and PostgreSQL.
     *
     * @param string $databaseUrl
     * @return \PDO|null
     */
    private function createDatabaseConnection(string $databaseUrl): ?\PDO
    {
        try {
            $parsed = parse_url($databaseUrl);

            if (!$parsed) {
                return null;
            }

            $scheme = $parsed['scheme'] ?? '';
            $host = $parsed['host'] ?? 'localhost';
            $dbname = ltrim($parsed['path'] ?? '', '/');
            $user = $parsed['user'] ?? '';
            $password = $parsed['pass'] ?? '';

            // Determine driver and default port
            if (in_array($scheme, ['mysql', 'mysqli'])) {
                $driver = 'mysql';
                $port = $parsed['port'] ?? 3306;
            } elseif (in_array($scheme, ['postgresql', 'postgres', 'pgsql'])) {
                $driver = 'pgsql';
                $port = $parsed['port'] ?? 5432;
            } else {
                error_log("Unsupported database driver: {$scheme}");
                return null;
            }

            $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname}";

            return new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

        } catch (\Exception $e) {
            error_log("Failed to create database connection: {$e->getMessage()}");
            return null;
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
