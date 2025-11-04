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
 * - Scans plugins directory for ALL plugins (from plugin.json files)
 * - Registers their controllers (if 'routes' capability)
 * - Loads their services from Resources/config/services.yaml
 */
class PluginCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Get project directory
        $projectDir = $container->getParameter('kernel.project_dir');

        // Scan plugins directory for ALL plugins (no database query needed)
        $plugins = $this->scanPluginsFromFilesystem($projectDir);

        if (empty($plugins)) {
            return; // No plugins to load
        }

        foreach ($plugins as $pluginData) {
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
     * Scan plugins directory and return ALL plugins.
     *
     * Reads plugin.json files from filesystem (no database connection needed).
     * Returns ALL plugins found in /plugins/ directory, regardless of enabled/disabled state.
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

                // Add plugin to result (no capability filtering - register all)
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
