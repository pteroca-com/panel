<?php

namespace App\Core\Trait;

use App\Core\Entity\Plugin;
use App\Core\Repository\PluginRepository;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait for selecting plugins in console commands.
 */
trait PluginSelectionTrait
{
    /**
     * Get plugins to process based on command arguments.
     *
     * @param PluginRepository $pluginRepository Repository to fetch plugins from
     * @param SymfonyStyle $io Console I/O helper
     * @param string|null $pluginName Optional plugin name to filter by
     * @param bool $includeDisabled Whether to include disabled plugins
     *
     * @return Plugin[]|null Array of plugins or null on error
     */
    protected function getPluginsForCommand(
        PluginRepository $pluginRepository,
        SymfonyStyle $io,
        ?string $pluginName,
        bool $includeDisabled
    ): ?array {
        // Get single plugin by name
        if ($pluginName) {
            $plugin = $pluginRepository->findByName($pluginName);
            if ($plugin === null) {
                $io->error(sprintf('Plugin "%s" not found', $pluginName));
                return null;
            }
            return [$plugin];
        }

        // Get all or enabled plugins
        return $includeDisabled
            ? $pluginRepository->findAll()
            : $pluginRepository->findEnabled();
    }

    /**
     * Validate and handle empty plugin list.
     *
     * @param SymfonyStyle $io Console I/O helper
     * @param array $plugins Array of plugins to check
     * @param string $action Action being performed (e.g., 'check', 'scan')
     *
     * @return bool True if plugins exist, false if empty
     */
    protected function validatePluginList(SymfonyStyle $io, array $plugins, string $action): bool
    {
        if (empty($plugins)) {
            $io->info(sprintf('No plugins to %s.', $action));
            return false;
        }

        return true;
    }
}
