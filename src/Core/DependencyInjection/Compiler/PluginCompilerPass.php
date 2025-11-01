<?php

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

/**
 * Compiler pass to dynamically register plugin services.
 *
 * This runs during container compilation and:
 * - Finds all ENABLED plugins from database
 * - Loads their services from Resources/config/services.yaml
 * - Registers event subscribers
 * - Creates service locators for plugin isolation
 */
class PluginCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Get project directory
        $projectDir = $container->getParameter('kernel.project_dir');

        // Get enabled plugins from database
        // Note: We need to bootstrap the database connection during compilation
        $enabledPlugins = $this->getEnabledPlugins($container);

        if (empty($enabledPlugins)) {
            return; // No plugins to load
        }

        foreach ($enabledPlugins as $pluginData) {
            $this->registerPlugin($container, $pluginData, $projectDir);
        }
    }

    /**
     * Register a single plugin's services.
     *
     * @param ContainerBuilder $container
     * @param array $pluginData ['name' => string, 'manifest' => array]
     * @param string $projectDir
     */
    private function registerPlugin(ContainerBuilder $container, array $pluginData, string $projectDir): void
    {
        $pluginName = $pluginData['name'];
        $manifest = $pluginData['manifest'];
        $pluginPath = $projectDir . '/plugins/' . $pluginName;

        // Step 0: Register autoloader for this plugin so classes can be found
        $this->registerPluginAutoloader($pluginName, $pluginPath);

        // Step 1: Register controllers if plugin has 'routes' capability
        if (in_array('routes', $manifest['capabilities'] ?? [], true)) {
            $this->registerPluginControllers($container, $pluginName, $pluginPath);
        }

        // Step 2: Register custom services from services.yaml
        $servicesPath = $pluginPath . '/Resources/config/services.yaml';

        // Check if services.yaml exists
        if (!file_exists($servicesPath)) {
            return; // No additional services to register
        }

        try {
            // Parse services.yaml
            $servicesConfig = Yaml::parseFile($servicesPath);

            if (!isset($servicesConfig['services'])) {
                return;
            }

            // Register each service from the plugin
            $this->registerPluginServices($container, $servicesConfig['services'], $pluginName);

        } catch (\Exception $e) {
            // Log error but don't break compilation
            // In production, you might want to mark plugin as FAULTED
            error_log("Failed to load services for plugin {$pluginName}: {$e->getMessage()}");
        }
    }

    /**
     * Register plugin services into the container.
     *
     * @param ContainerBuilder $container
     * @param array $services
     * @param string $pluginName
     */
    private function registerPluginServices(ContainerBuilder $container, array $services, string $pluginName): void
    {
        foreach ($services as $serviceId => $serviceConfig) {
            // Skip special keys like _defaults
            if (strpos($serviceId, '_') === 0) {
                continue;
            }

            // Prefix service ID with plugin name for isolation
            $prefixedServiceId = "plugin.{$pluginName}.{$serviceId}";

            // Get service class
            $class = $serviceConfig['class'] ?? $serviceId;

            // Register service definition
            $definition = $container->register($prefixedServiceId, $class);

            // Apply configuration
            if (isset($serviceConfig['arguments'])) {
                $definition->setArguments($this->resolveArguments($serviceConfig['arguments']));
            }

            if (isset($serviceConfig['tags'])) {
                foreach ($serviceConfig['tags'] as $tag) {
                    $definition->addTag(is_array($tag) ? $tag['name'] : $tag);
                }
            }

            if (isset($serviceConfig['public'])) {
                $definition->setPublic($serviceConfig['public']);
            }

            if (isset($serviceConfig['autowire'])) {
                $definition->setAutowired($serviceConfig['autowire']);
            }

            if (isset($serviceConfig['autoconfigure'])) {
                $definition->setAutoconfigured($serviceConfig['autoconfigure']);
            }
        }
    }

    /**
     * Resolve service arguments (convert string references to Reference objects).
     *
     * @param array $arguments
     * @return array
     */
    private function resolveArguments(array $arguments): array
    {
        $resolved = [];

        foreach ($arguments as $argument) {
            if (is_string($argument) && strpos($argument, '@') === 0) {
                // Service reference
                $serviceName = substr($argument, 1);
                $resolved[] = new Reference($serviceName);
            } else {
                $resolved[] = $argument;
            }
        }

        return $resolved;
    }

    /**
     * Get enabled plugins from database.
     *
     * This method establishes a temporary database connection during compilation.
     *
     * @param ContainerBuilder $container
     * @return array
     */
    private function getEnabledPlugins(ContainerBuilder $container): array
    {
        try {
            // Get database URL from environment variable
            $databaseUrl = getenv('DATABASE_URL');

            // Check if database URL is set
            if (empty($databaseUrl)) {
                // Try to get from container parameter (might be set in parameters.yaml)
                if ($container->hasParameter('database_url')) {
                    $databaseUrl = $container->getParameter('database_url');
                } else {
                    return []; // No database configured
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
            // Silently fail - plugin table might not exist yet during initial setup
            error_log("Could not load plugins during compilation: {$e->getMessage()}");
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
            // Parse DATABASE_URL (format: mysql://user:pass@host:port/dbname or postgresql://...)
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
     * Register plugin controllers as services.
     *
     * This makes controllers available for autowiring and dependency injection.
     *
     * @param ContainerBuilder $container
     * @param string $pluginName
     * @param string $pluginPath
     */
    private function registerPluginControllers(ContainerBuilder $container, string $pluginName, string $pluginPath): void
    {
        $controllerPath = $pluginPath . '/src/Controller';

        if (!is_dir($controllerPath)) {
            return;
        }

        // Get plugin namespace
        $pluginNamespace = $this->getPluginNamespace($pluginName);

        try {
            // Find all PHP files in Controller directory
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($controllerPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Extract class name from file
                $relativePath = str_replace($controllerPath . '/', '', $file->getPathname());
                $className = $pluginNamespace . '\\Controller\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

                // Register controller as service with autowiring
                // AbstractController automatically gets the service locator through parent::setContainer()
                $definition = $container->register($className, $className);
                $definition->setPublic(true);
                $definition->setAutowired(true);
                $definition->addTag('controller.service_arguments');

                // AbstractController extends from ContainerAwareInterface and needs container
                // Symfony will automatically inject the service locator
                $definition->addTag('container.service_subscriber');
            }

        } catch (\Exception $e) {
            error_log("Failed to register controllers for plugin {$pluginName}: {$e->getMessage()}");
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
        // Convert "hello-world" to "HelloWorld"
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\{$className}";
    }

    /**
     * Register PSR-4 autoloader for a plugin.
     *
     * This allows plugin classes to be found during container compilation.
     *
     * @param string $pluginName
     * @param string $pluginPath
     */
    private function registerPluginAutoloader(string $pluginName, string $pluginPath): void
    {
        $srcPath = $pluginPath . '/src';

        if (!is_dir($srcPath)) {
            return;
        }

        $namespace = $this->getPluginNamespace($pluginName) . '\\';

        spl_autoload_register(function ($class) use ($namespace, $srcPath) {
            // Check if class belongs to this namespace
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            // Get relative class name
            $relativeClass = substr($class, strlen($namespace));

            // Replace namespace separators with directory separators
            $file = $srcPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            // Require if exists
            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
