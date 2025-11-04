<?php

namespace App\Core\Trait;

use Exception;

/**
 * Trait for scanning plugin directories from filesystem.
 *
 * Provides common functionality for discovering plugins by scanning
 * the filesystem for plugin.json manifest files.
 */
trait PluginDirectoryScannerTrait
{
    /**
     * Scan a plugins directory and return discovered plugins with their manifests.
     *
     * @param string $pluginsDir Path to plugins directory
     * @param bool $parseManifest Whether to parse JSON manifest (true) or return raw content (false)
     * @return array Array of plugin data: ['name' => string, 'manifest' => array|string, 'path' => string]
     */
    protected function scanPluginDirectory(string $pluginsDir, bool $parseManifest = true): array
    {
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
                // Skip . and .. and hidden directories
                if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
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
                    error_log("Could not read plugin manifest: $manifestPath");
                    continue;
                }

                if ($parseManifest) {
                    $manifest = json_decode($manifestContent, true);

                    if (!$manifest || json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Invalid JSON in plugin manifest: $manifestPath");
                        continue;
                    }
                } else {
                    $manifest = $manifestContent;
                }

                // Add plugin to result
                $plugins[] = [
                    'name' => $dir,
                    'manifest' => $manifest,
                    'path' => $pluginPath,
                ];
            }

            return $plugins;

        } catch (Exception $e) {
            error_log("Error scanning plugins directory: {$e->getMessage()}");
            return [];
        }
    }
}
