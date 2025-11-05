<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

readonly class PluginLoader
{
    public function __construct(
        private PluginAutoloader              $autoloader,
        private PluginEventSubscriberRegistry $eventSubscriberRegistry,
        private PluginCommandRegistry         $commandRegistry,
        private PluginCronRegistry            $cronRegistry,
        private LoggerInterface               $logger,
        private string                        $projectDir,
    ) {}

    /**
     * @throws Exception If loading fails
     */
    public function load(Plugin $plugin): void
    {
        if (!$plugin->isEnabled()) {
            throw new RuntimeException("Cannot load plugin that is not enabled: {$plugin->getName()}");
        }

        try {
            // Step 1: Register PSR-4 autoloading
            $this->registerAutoloading($plugin);

            // Step 2: Load plugin services (if services.yaml exists)
            $this->loadServices($plugin);

            // Step 3: Register event subscribers (if 'eda' capability)
            $this->registerEventSubscribers($plugin);

            // Step 4: Register console commands (if 'console' capability)
            $this->registerCommands($plugin);

            // Step 5: Register cron tasks (if 'cron' capability)
            $this->registerCronTasks($plugin);

            $this->logger->info("Plugin loaded successfully", [
                'plugin' => $plugin->getName(),
                'version' => $plugin->getVersion(),
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load plugin", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException("Failed to load plugin {$plugin->getName()}: {$e->getMessage()}", 0, $e);
        }
    }

    public function unload(Plugin $plugin): void
    {
        try {
            // Unregister event subscribers
            $this->unregisterEventSubscribers($plugin);

            // Unregister console commands
            $this->unregisterCommands($plugin);

            // Unregister cron tasks
            $this->unregisterCronTasks($plugin);

            // Unregister PSR-4 autoloading
            $this->autoloader->unregisterPlugin($plugin);

            $this->logger->info("Plugin unloaded", [
                'plugin' => $plugin->getName(),
            ]);

        } catch (Exception $e) {
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
        return "Plugins\\$className\\";
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

    /**
     * Register event subscribers for a plugin.
     *
     * @param Plugin $plugin
     */
    private function registerEventSubscribers(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('eda')) {
            return;
        }

        try {
            $this->eventSubscriberRegistry->registerSubscribers($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to register event subscribers", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow plugin to load without subscribers
        }
    }

    /**
     * Unregister event subscribers for a plugin.
     *
     * @param Plugin $plugin
     */
    private function unregisterEventSubscribers(Plugin $plugin): void
    {
        try {
            $this->eventSubscriberRegistry->unregisterSubscribers($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to unregister event subscribers", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register console commands for a plugin.
     *
     * @param Plugin $plugin
     */
    private function registerCommands(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('console')) {
            return;
        }

        try {
            $this->commandRegistry->registerCommands($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to register console commands", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow plugin to load without commands
        }
    }

    /**
     * Unregister console commands for a plugin.
     *
     * @param Plugin $plugin
     */
    private function unregisterCommands(Plugin $plugin): void
    {
        try {
            $this->commandRegistry->unregisterCommands($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to unregister console commands", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register cron tasks for a plugin.
     *
     * @param Plugin $plugin
     */
    private function registerCronTasks(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('cron')) {
            return;
        }

        try {
            $this->cronRegistry->registerTasks($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to register cron tasks", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow plugin to load without cron tasks
        }
    }

    /**
     * Unregister cron tasks for a plugin.
     *
     * @param Plugin $plugin
     */
    private function unregisterCronTasks(Plugin $plugin): void
    {
        try {
            $this->cronRegistry->unregisterTasks($plugin);
        } catch (Exception $e) {
            $this->logger->warning("Failed to unregister cron tasks", [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
