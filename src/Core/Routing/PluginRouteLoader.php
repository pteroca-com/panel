<?php

namespace App\Core\Routing;

use App\Core\Entity\Plugin;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use App\Core\Repository\PluginRepository;
use Symfony\Component\Config\Loader\Loader;
use App\Core\Service\Plugin\PluginAutoloader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Loader\AttributeClassLoader;

class PluginRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly PluginAutoloader $autoloader,
        private readonly AttributeClassLoader $annotationLoader,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new RuntimeException('Do not add the "plugin" loader twice');
        }

        $this->isLoaded = true;

        $routes = new RouteCollection();

        try {
            // Get all enabled plugins
            $enabledPlugins = $this->pluginRepository->findEnabled();

            // Register autoloaders for all enabled plugins
            foreach ($enabledPlugins as $plugin) {
                $this->autoloader->registerPlugin($plugin);
            }

            foreach ($enabledPlugins as $plugin) {
                // Check if plugin has 'routes' capability
                if (!$plugin->hasCapability('routes')) {
                    $this->logger->debug("Plugin {$plugin->getName()} does not have 'routes' capability, skipping route loading");
                    continue;
                }

                $pluginRoutes = $this->loadPluginRoutes($plugin);

                if ($pluginRoutes->count() > 0) {
                    $routes->addCollection($pluginRoutes);
                    $this->logger->info("Loaded {$pluginRoutes->count()} routes from plugin {$plugin->getName()}");
                }
            }
        } catch (Exception $e) {
            // Database not available (e.g., during cache warmup without DB connection)
            // or plugin table doesn't exist yet
            $this->logger->warning("Could not load plugin routes: {$e->getMessage()}");
        }

        return $routes;
    }

    private function loadPluginRoutes(Plugin $plugin): RouteCollection
    {
        $routes = new RouteCollection();

        $controllerPath = $this->projectDir . '/plugins/' . $plugin->getName() . '/src/Controller';

        // Check if Controller directory exists
        if (!is_dir($controllerPath)) {
            $this->logger->debug("Controller directory not found for plugin {$plugin->getName()}: $controllerPath");
            return $routes;
        }

        try {
            // Find all PHP files in Controller directory
            $finder = new Finder();
            $finder->files()->name('*.php')->in($controllerPath);

            if (!$finder->hasResults()) {
                return $routes;
            }

            // Get plugin namespace
            $pluginNamespace = $this->getPluginNamespace($plugin->getName());

            foreach ($finder as $file) {
                $className = $this->getClassNameFromFile($file->getRealPath(), $pluginNamespace);

                if ($className && class_exists($className)) {
                    try {
                        // Load routes from controller class
                        $classRoutes = $this->annotationLoader->load($className);

                        if ($classRoutes->count() > 0) {
                            // Add prefix to all routes
                            $classRoutes->addPrefix('/plugins/' . $plugin->getName());

                            // Add routes to collection
                            $routes->addCollection($classRoutes);

                            $this->logger->debug("Loaded {$classRoutes->count()} routes from $className");
                        }
                    } catch (Exception $e) {
                        $this->logger->error("Failed to load routes from $className: {$e->getMessage()}");
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to load routes from plugin {$plugin->getName()}: {$e->getMessage()}");
        }

        return $routes;
    }

    private function getPluginNamespace(string $pluginName): string
    {
        // Convert "hello-world" to "HelloWorld"
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));

        return "Plugins\\$className";
    }

    private function getClassNameFromFile(string $filePath, string $baseNamespace): ?string
    {
        // Extract relative path from Controller directory
        $pathParts = explode('/Controller/', $filePath);
        if (count($pathParts) !== 2) {
            return null;
        }

        $relativePath = $pathParts[1];

        // Remove .php extension
        $relativePath = substr($relativePath, 0, -4);

        // Convert path to namespace
        $classPath = str_replace('/', '\\', $relativePath);

        return "$baseNamespace\\Controller\\$classPath";
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return 'plugin' === $type;
    }
}
