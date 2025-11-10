<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Psr\Log\LoggerInterface;

class PluginAutoloader
{
    private array $registeredNamespaces = [];

    /**
     * Stores Composer ClassLoaders for plugins.
     * Used to unregister them when plugin is disabled.
     *
     * @var array<string, \Composer\Autoload\ClassLoader>
     */
    private array $composerLoaders = [];

    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {}

    public function registerPlugin(Plugin $plugin): bool
    {
        if (!$plugin->isEnabled()) {
            return false;
        }

        $namespace = $this->getPluginNamespace($plugin->getName());
        $path = $this->getPluginSrcPath($plugin->getName());

        if (!is_dir($path)) {
            return false;
        }

        // Register plugin's PSR-4 autoloading
        $registered = $this->registerNamespace($namespace, $path);

        if (!$registered) {
            return false;
        }

        // Load Composer dependencies if vendor/autoload.php exists
        $this->loadComposerDependencies($plugin);

        return true;
    }

    public function unregisterPlugin(Plugin $plugin): void
    {
        $namespace = $this->getPluginNamespace($plugin->getName());
        $this->unregisterNamespace($namespace);

        // Unregister Composer autoloader if exists
        $pluginName = $plugin->getName();
        if (isset($this->composerLoaders[$pluginName])) {
            $this->composerLoaders[$pluginName]->unregister();
            unset($this->composerLoaders[$pluginName]);

            $this->logger->info('Unregistered Composer autoloader for plugin', [
                'plugin' => $pluginName,
            ]);
        }
    }

    private function registerNamespace(string $namespace, string $path): bool
    {
        if (isset($this->registeredNamespaces[$namespace])) {
            return false; // Already registered
        }

        spl_autoload_register(function ($class) use ($namespace, $path) {
            // Check if class belongs to this namespace
            if (!str_starts_with($class, $namespace)) {
                return;
            }

            // Get relative class name
            $relativeClass = substr($class, strlen($namespace));

            // Replace namespace separators with directory separators
            $file = $path . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            // Require if exists
            if (file_exists($file)) {
                require $file;
            }
        });

        $this->registeredNamespaces[$namespace] = $path;

        return true;
    }

    private function unregisterNamespace(string $namespace): void
    {
        unset($this->registeredNamespaces[$namespace]);
    }

    /**
     * Load Composer dependencies from plugin's vendor/autoload.php.
     *
     * This method loads the Composer autoloader for a plugin if it exists.
     * The autoloader is registered without prepend to avoid overriding core classes.
     */
    private function loadComposerDependencies(Plugin $plugin): void
    {
        $pluginName = $plugin->getName();
        $vendorAutoloadPath = $this->projectDir . '/plugins/' . $pluginName . '/vendor/autoload.php';

        // Check if vendor/autoload.php exists
        if (!file_exists($vendorAutoloadPath)) {
            return;
        }

        try {
            // Require vendor/autoload.php - returns ClassLoader instance
            /** @var \Composer\Autoload\ClassLoader|null $loader */
            $loader = require $vendorAutoloadPath;

            if ($loader === null) {
                $this->logger->warning('Plugin vendor/autoload.php did not return ClassLoader', [
                    'plugin' => $pluginName,
                    'path' => $vendorAutoloadPath,
                ]);
                return;
            }

            // IMPORTANT: Unregister first, then re-register without prepend
            // This ensures plugin dependencies don't override core classes
            $loader->unregister();
            $loader->register(false); // prepend = false

            // Store loader reference for unregistering later
            $this->composerLoaders[$pluginName] = $loader;

            $this->logger->info('Loaded Composer dependencies for plugin', [
                'plugin' => $pluginName,
                'prepend' => false,
                'vendor_path' => $vendorAutoloadPath,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to load Composer dependencies for plugin', [
                'plugin' => $pluginName,
                'error' => $e->getMessage(),
                'path' => $vendorAutoloadPath,
            ]);
        }
    }

    private function getPluginNamespace(string $pluginName): string
    {
        // Convert "hello-world" to "HelloWorld"
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));

        return "Plugins\\$className\\";
    }

    private function getPluginSrcPath(string $pluginName): string
    {
        return $this->projectDir . '/plugins/' . $pluginName . '/src';
    }

    /**
     * @return array<string, string> Namespace => Path
     */
    public function getRegisteredNamespaces(): array
    {
        return $this->registeredNamespaces;
    }

    public function isNamespaceRegistered(string $namespace): bool
    {
        return isset($this->registeredNamespaces[$namespace]);
    }
}
