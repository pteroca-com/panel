<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Psr\Log\LoggerInterface;

class PluginLoader
{
    public function __construct(
        private readonly PluginAutoloader $autoloader,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {}

    /**
     * @throws \Exception If loading fails
     */
    public function load(Plugin $plugin): void
    {
        if (!$plugin->isEnabled()) {
            throw new \RuntimeException("Cannot load plugin that is not enabled: {$plugin->getName()}");
        }

        try {
            // Step 1: Register PSR-4 autoloading
            $this->registerAutoloading($plugin);

            // Step 2: Load plugin services (if services.yaml exists)
            $this->loadServices($plugin);

            $this->logger->info("Plugin loaded successfully", [
                'plugin' => $plugin->getName(),
                'version' => $plugin->getVersion(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Failed to load plugin", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to load plugin {$plugin->getName()}: {$e->getMessage()}", 0, $e);
        }
    }

    public function unload(Plugin $plugin): void
    {
        try {
            // Unregister PSR-4 autoloading
            $this->autoloader->unregisterPlugin($plugin);

            $this->logger->info("Plugin unloaded", [
                'plugin' => $plugin->getName(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Failed to unload plugin", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function registerAutoloading(Plugin $plugin): void
    {
        $registered = $this->autoloader->registerPlugin($plugin);

        if (!$registered) {
            $this->logger->warning("Plugin autoloading could not be registered", [
                'plugin' => $plugin->getName(),
            ]);
        } else {
            $this->logger->debug("Plugin autoloading registered", [
                'plugin' => $plugin->getName(),
                'namespace' => $this->getPluginNamespace($plugin->getName()),
            ]);
        }
    }

    private function loadServices(Plugin $plugin): void
    {
        $servicesPath = $this->getPluginPath($plugin->getName()) . '/Resources/config/services.yaml';

        if (file_exists($servicesPath)) {
            $this->logger->debug("Plugin services file found", [
                'plugin' => $plugin->getName(),
                'path' => $servicesPath,
            ]);
        }
    }

    private function getPluginNamespace(string $pluginName): string
    {
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\{$className}\\";
    }

    private function getPluginPath(string $pluginName): string
    {
        return $this->projectDir . '/plugins/' . $pluginName;
    }

    public function canLoad(Plugin $plugin): bool
    {
        if (!$plugin->isEnabled()) {
            return false;
        }

        // Check if src directory exists
        $srcPath = $this->getPluginPath($plugin->getName()) . '/src';
        if (!is_dir($srcPath)) {
            $this->logger->warning("Plugin src directory not found", [
                'plugin' => $plugin->getName(),
                'path' => $srcPath,
            ]);
            return false;
        }

        return true;
    }
}
