<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Psr\Log\LoggerInterface;
use App\Core\Enum\PluginStateEnum;
use App\Core\DTO\PluginManifestDTO;
use Doctrine\ORM\EntityManagerInterface;
use App\Core\Repository\PluginRepository;
use App\Core\Event\Plugin\PluginEnabledEvent;
use App\Core\Event\Plugin\PluginFaultedEvent;
use App\Core\Event\Plugin\PluginUpdatedEvent;
use App\Core\Event\Plugin\PluginDisabledEvent;
use App\Core\Event\Plugin\PluginDiscoveredEvent;
use App\Core\Event\Plugin\PluginRegisteredEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Core\Exception\Plugin\InvalidStateTransitionException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PluginManager
{
    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly PluginScanner $pluginScanner,
        private readonly ManifestParser $manifestParser,
        private readonly ManifestValidator $manifestValidator,
        private readonly PluginStateMachine $stateMachine,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly PluginLoader $pluginLoader,
        private readonly PluginMigrationService $migrationService,
        private readonly KernelInterface $kernel,
    ) {}

    /**
     * @return array{discovered: int, registered: int, failed: int, errors: array}
     */
    public function discoverAndRegisterPlugins(): array
    {
        $discovered = 0;
        $registered = 0;
        $failed = 0;
        $errors = [];

        // Scan plugins directory
        $scannedPlugins = $this->pluginScanner->scan();

        foreach ($scannedPlugins as $pluginName => $data) {
            ++$discovered;

            try {
                // Check if plugin already exists
                $existingPlugin = $this->pluginRepository->findByName($pluginName);

                if ($existingPlugin !== null) {
                    // Check for version update
                    if ($existingPlugin->getVersion() !== $data['manifest']->version) {
                        $this->handlePluginUpdate($existingPlugin, $data['manifest']);
                    }
                    continue;
                }

                // Validate manifest
                if (count($data['errors']) > 0) {
                    $errors[$pluginName] = $data['errors'];
                    ++$failed;
                    $this->logger->warning("Plugin {$pluginName} has validation errors", $data['errors']);
                    continue;
                }

                // Register new plugin
                $plugin = $this->registerPlugin($data['path'], $data['manifest']);
                ++$registered;

                $this->logger->info("Registered new plugin: {$pluginName}");
            } catch (\Exception $e) {
                ++$failed;
                $errors[$pluginName] = [$e->getMessage()];
                $this->logger->error("Failed to register plugin {$pluginName}: {$e->getMessage()}");
            }
        }

        return [
            'discovered' => $discovered,
            'registered' => $registered,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function registerPlugin(string $pluginPath, \App\Core\DTO\PluginManifestDTO $manifest): Plugin
    {
        // Create plugin entity
        $plugin = new Plugin();
        $plugin->setName($manifest->name);
        $plugin->setDisplayName($manifest->displayName);
        $plugin->setVersion($manifest->version);
        $plugin->setAuthor($manifest->author);
        $plugin->setDescription($manifest->description);
        $plugin->setLicense($manifest->license);
        $plugin->setPterocaMinVersion($manifest->getMinPterocaVersion());
        $plugin->setPterocaMaxVersion($manifest->getMaxPterocaVersion());
        $plugin->setPath($pluginPath);
        $plugin->setManifest($manifest->raw);

        // Validate PteroCA compatibility
        if (!$this->manifestValidator->isCompatibleWithPteroCA($manifest)) {
            $errorMessage = $this->manifestValidator->getCompatibilityError($manifest);
            $this->stateMachine->transitionToFaulted($plugin, $errorMessage);

            // Dispatch faulted event
            $this->eventDispatcher->dispatch(new PluginFaultedEvent($plugin, $errorMessage));
        } else {
            // Transition to REGISTERED state
            $this->stateMachine->transitionToRegistered($plugin);

            // Dispatch discovered and registered events
            $this->eventDispatcher->dispatch(new PluginDiscoveredEvent($pluginPath, $manifest));
            $this->eventDispatcher->dispatch(new PluginRegisteredEvent($plugin));
        }

        // Persist to database
        $this->pluginRepository->save($plugin, true);

        return $plugin;
    }

    public function enablePlugin(Plugin $plugin): void
    {
        // Validate state transition
        $this->stateMachine->validateTransition($plugin, PluginStateEnum::ENABLED);

        // Transition to ENABLED state
        $this->stateMachine->transitionToEnabled($plugin);

        // Persist changes
        $this->pluginRepository->save($plugin, true);

        // Load plugin (register autoloading, services, etc.)
        try {
            $this->pluginLoader->load($plugin);

            // Execute database migrations
            $migrationResult = $this->migrationService->executeMigrations($plugin);

            if (!$migrationResult['skipped'] && $migrationResult['executed'] > 0) {
                $this->logger->info("Executed {$migrationResult['executed']} migrations for plugin {$plugin->getName()}");
            }

        } catch (\Exception $e) {
            // If loading or migrations fail, mark as faulted
            $this->stateMachine->transitionToFaulted($plugin, $e->getMessage());
            $this->pluginRepository->save($plugin, true);

            $this->eventDispatcher->dispatch(new PluginFaultedEvent($plugin, $e->getMessage()));

            throw new \RuntimeException("Failed to load plugin {$plugin->getName()}: {$e->getMessage()}", 0, $e);
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new PluginEnabledEvent($plugin));

        $this->logger->info("Plugin enabled: {$plugin->getName()}");

        // Clear cache to reload routes, entities, etc.
        $this->clearCache();
    }

    /**
     * @throws InvalidStateTransitionException If plugin cannot be disabled
     */
    public function disablePlugin(Plugin $plugin): void
    {
        // Validate state transition
        $this->stateMachine->validateTransition($plugin, PluginStateEnum::DISABLED);

        // Unload plugin (unregister autoloading, etc.)
        $this->pluginLoader->unload($plugin);

        // Transition to DISABLED state
        $this->stateMachine->transitionToDisabled($plugin);

        // Persist changes
        $this->pluginRepository->save($plugin, true);

        // Dispatch event
        $this->eventDispatcher->dispatch(new PluginDisabledEvent($plugin));

        $this->logger->info("Plugin disabled: {$plugin->getName()}");

        // Clear cache to reload routes, entities, etc.
        $this->clearCache();
    }

    private function handlePluginUpdate(Plugin $plugin, PluginManifestDTO $newManifest): void
    {
        $oldVersion = $plugin->getVersion();
        $newVersion = $newManifest->version;

        // Update plugin entity
        $plugin->setVersion($newVersion);
        $plugin->setManifest($newManifest->raw);

        // Transition to UPDATE_PENDING if plugin is enabled
        if ($plugin->getState() === PluginStateEnum::ENABLED) {
            $this->stateMachine->transitionToUpdatePending($plugin);
        }

        // Persist changes
        $this->pluginRepository->save($plugin, true);

        // Dispatch event
        $this->eventDispatcher->dispatch(new PluginUpdatedEvent($plugin, $oldVersion, $newVersion));

        $this->logger->info("Plugin updated: {$plugin->getName()} {$oldVersion} → {$newVersion}");
    }

    public function getPluginByName(string $name): ?Plugin
    {
        return $this->pluginRepository->findByName($name);
    }

    /**
     * @return Plugin[] Array of all plugins
     */
    public function getAllPlugins(): array
    {
        return $this->pluginRepository->findAll();
    }

    /**
     * @return Plugin[] Array of enabled plugins
     */
    public function getEnabledPlugins(): array
    {
        return $this->pluginRepository->findEnabled();
    }

    /**
     * @return Plugin[] Array of disabled plugins
     */
    public function getDisabledPlugins(): array
    {
        return $this->pluginRepository->findDisabled();
    }

    /**
     * @return Plugin[] Array of faulted plugins
     */
    public function getFaultedPlugins(): array
    {
        return $this->pluginRepository->findFaulted();
    }

    public function hasPlugin(string $name): bool
    {
        return $this->pluginRepository->existsByName($name);
    }

    /**
     * @return array{total: int, enabled: int, disabled: int, faulted: int} Plugin statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->pluginRepository->count([]),
            'enabled' => $this->pluginRepository->countByState(PluginStateEnum::ENABLED),
            'disabled' => $this->pluginRepository->countByState(PluginStateEnum::DISABLED),
            'faulted' => $this->pluginRepository->countByState(PluginStateEnum::FAULTED),
        ];
    }

    /**
     * @return Plugin[] Array of Plugin entities
     */
    public function getAllPluginsFromFilesystem(): array
    {
        $plugins = [];
        $scannedPlugins = $this->pluginScanner->scanValid();

        foreach ($scannedPlugins as $pluginName => $data) {
            $manifest = $data['manifest'];
            $pluginPath = $data['path'];

            // Check if plugin exists in database
            $existingPlugin = $this->pluginRepository->findByName($pluginName);

            if ($existingPlugin !== null) {
                // Check for version update
                if ($existingPlugin->getVersion() !== $manifest->version) {
                    $existingPlugin->setVersion($manifest->version);
                    $existingPlugin->setManifest($manifest->raw);
                    if ($existingPlugin->getState() === PluginStateEnum::ENABLED) {
                        $existingPlugin->setState(PluginStateEnum::UPDATE_PENDING);
                    }
                }
                $plugins[] = $existingPlugin;
            } else {
                // Create virtual plugin entity (not persisted)
                $plugin = $this->createPluginFromManifest($pluginPath, $manifest);
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * @throws \RuntimeException If plugin not found in filesystem
     */
    public function getOrCreatePlugin(string $pluginName): Plugin
    {
        // Check database first
        $plugin = $this->pluginRepository->findByName($pluginName);
        if ($plugin !== null) {
            return $plugin;
        }

        // Scan filesystem
        $pluginData = $this->pluginScanner->discoverPlugin(
            $this->pluginScanner->getPluginPath($pluginName)
        );

        if ($pluginData === null) {
            throw new \RuntimeException("Plugin '{$pluginName}' not found in filesystem");
        }

        if (count($pluginData['errors']) > 0) {
            throw new \RuntimeException(
                "Plugin '{$pluginName}' has validation errors: " . implode(', ', $pluginData['errors'])
            );
        }

        // Create plugin entity from manifest
        $plugin = $this->createPluginFromManifest($pluginData['path'], $pluginData['manifest']);

        // Persist to database
        $this->pluginRepository->save($plugin, true);

        // Dispatch events
        $this->eventDispatcher->dispatch(new PluginDiscoveredEvent($pluginData['path'], $pluginData['manifest']));
        $this->eventDispatcher->dispatch(new PluginRegisteredEvent($plugin));

        $this->logger->info("Created plugin entity: {$pluginName}");

        return $plugin;
    }

    private function createPluginFromManifest(string $pluginPath, \App\Core\DTO\PluginManifestDTO $manifest): Plugin
    {
        $plugin = new Plugin();
        $plugin->setName($manifest->name);
        $plugin->setDisplayName($manifest->displayName);
        $plugin->setVersion($manifest->version);
        $plugin->setAuthor($manifest->author);
        $plugin->setDescription($manifest->description);
        $plugin->setLicense($manifest->license);
        $plugin->setPterocaMinVersion($manifest->getMinPterocaVersion());
        $plugin->setPterocaMaxVersion($manifest->getMaxPterocaVersion());
        $plugin->setPath($pluginPath);
        $plugin->setManifest($manifest->raw);
        $plugin->setState(PluginStateEnum::DISCOVERED);

        // Validate PteroCA compatibility
        if (!$this->manifestValidator->isCompatibleWithPteroCA($manifest)) {
            $errorMessage = $this->manifestValidator->getCompatibilityError($manifest);
            $plugin->setState(PluginStateEnum::FAULTED);
            $plugin->setFaultReason($errorMessage);
        }

        return $plugin;
    }

    private function clearCache(): void
    {
        $cacheDir = $this->kernel->getCacheDir();

        // Register shutdown function to clear cache after script completes
        // This is the safest way to avoid errors when deleting files that are currently in use
        register_shutdown_function(function () use ($cacheDir) {
            // Recursive function to remove directory contents
            $removeDir = function (string $dir) use (&$removeDir) {
                if (!is_dir($dir)) {
                    return;
                }

                $items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);

                foreach ($items as $item) {
                    if ($item->isDir()) {
                        $removeDir($item->getPathname());
                        @rmdir($item->getPathname());
                    } else {
                        // Don't remove .gitkeep files
                        if ($item->getFilename() !== '.gitkeep') {
                            @unlink($item->getPathname());
                        }
                    }
                }
            };

            try {
                $removeDir($cacheDir);
            } catch (\Throwable $e) {
                // Silently fail - cache will be rebuilt on next request
            }
        });

        $this->logger->info("Scheduled cache clearing for after process completion");
    }
}
