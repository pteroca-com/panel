<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use App\Core\Repository\PluginRepository;
use Exception;
use FilesystemIterator;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Manages plugin assets (CSS, JS, images, fonts).
 *
 * This service handles:
 * - Publishing assets to public directory (symlink or copy)
 * - Unpublishing assets when plugin is disabled
 * - Generating public asset URLs
 * - Managing asset files from plugin manifests
 */
readonly class PluginAssetManager
{
    public function __construct(
        private PluginRepository $pluginRepository,
        private LoggerInterface  $logger,
        private string           $projectDir,
        private string           $publicDir,
    ) {}

    /**
     * Publish assets for an enabled plugin.
     *
     * Creates a symlink from /plugins/{name}/assets/ to /public/plugins/{name}/
     * Falls back to copying files if symlink creation fails (e.g., on Windows).
     */
    public function publishAssets(Plugin $plugin): void
    {
        $assetsPath = $this->getPluginAssetsPath($plugin->getName());

        // Check if plugin has assets directory
        if (!is_dir($assetsPath)) {
            $this->logger->debug("No assets directory for plugin {$plugin->getName()}");
            return;
        }

        $publicPath = $this->getPublicPluginPath($plugin->getName());

        // Create public/plugins/ directory if it doesn't exist
        $publicPluginsDir = dirname($publicPath);
        if (!is_dir($publicPluginsDir)) {
            mkdir($publicPluginsDir, 0755, true);
            $this->logger->info("Created public plugins directory: $publicPluginsDir");
        }

        // Try to create symlink first (faster), fallback to copy
        if ($this->createSymlink($assetsPath, $publicPath)) {
            $this->logger->info("Published assets for plugin {$plugin->getName()} (symlink)");
        } else {
            $this->copyAssets($assetsPath, $publicPath);
            $this->logger->info("Published assets for plugin {$plugin->getName()} (copy)");
        }
    }

    /**
     * Unpublish assets for a disabled plugin.
     *
     * Removes symlink or copied files from /public/plugins/{name}/
     */
    public function unpublishAssets(Plugin $plugin): void
    {
        $publicPath = $this->getPublicPluginPath($plugin->getName());

        if (!file_exists($publicPath)) {
            $this->logger->debug("No published assets for plugin {$plugin->getName()}");
            return;
        }

        // Remove symlink or directory
        if (is_link($publicPath)) {
            unlink($publicPath);
            $this->logger->info("Removed asset symlink for plugin {$plugin->getName()}");
        } elseif (is_dir($publicPath)) {
            $this->removeDirectory($publicPath);
            $this->logger->info("Removed asset files for plugin {$plugin->getName()}");
        }
    }

    /**
     * Get public URL for a plugin asset.
     *
     * @param string $pluginName Plugin system name (e.g., 'hello-world')
     * @param string $assetPath Relative path within plugin assets (e.g., 'css/style.css')
     * @return string Public URL (e.g., '/plugins/hello-world/css/style.css')
     */
    public function getAssetUrl(string $pluginName, string $assetPath): string
    {
        // Remove leading slash if present
        $assetPath = ltrim($assetPath, '/');

        return sprintf('/plugins/%s/%s', $pluginName, $assetPath);
    }

    /**
     * Get all CSS assets from enabled plugins.
     *
     * @return string[] Array of CSS asset URLs
     */
    public function getGlobalCssAssets(): array
    {
        $assets = [];
        $enabledPlugins = $this->pluginRepository->findEnabled();

        foreach ($enabledPlugins as $plugin) {
            $manifest = $plugin->getManifest();

            if (isset($manifest['assets']['css']) && is_array($manifest['assets']['css'])) {
                foreach ($manifest['assets']['css'] as $cssFile) {
                    $assets[] = $this->getAssetUrl($plugin->getName(), $cssFile);
                }
            }
        }

        return $assets;
    }

    /**
     * Get all JS assets from enabled plugins.
     *
     * @return string[] Array of JS asset URLs
     */
    public function getGlobalJsAssets(): array
    {
        $assets = [];
        $enabledPlugins = $this->pluginRepository->findEnabled();

        foreach ($enabledPlugins as $plugin) {
            $manifest = $plugin->getManifest();

            if (isset($manifest['assets']['js']) && is_array($manifest['assets']['js'])) {
                foreach ($manifest['assets']['js'] as $jsFile) {
                    $assets[] = $this->getAssetUrl($plugin->getName(), $jsFile);
                }
            }
        }

        return $assets;
    }

    /**
     * Get plugin assets source path.
     *
     * @param string $pluginName Plugin system name
     * @return string Absolute path to plugin assets directory
     */
    private function getPluginAssetsPath(string $pluginName): string
    {
        return $this->projectDir . '/plugins/' . $pluginName . '/assets';
    }

    /**
     * Get public plugin path (where assets are published).
     *
     * @param string $pluginName Plugin system name
     * @return string Absolute path to public plugin directory
     */
    private function getPublicPluginPath(string $pluginName): string
    {
        return $this->publicDir . '/plugins/' . $pluginName;
    }

    /**
     * Create a symlink from source to target.
     *
     * @param string $source Source directory path
     * @param string $target Target symlink path
     * @return bool True if symlink created successfully, false otherwise
     */
    private function createSymlink(string $source, string $target): bool
    {
        try {
            // Remove existing symlink or directory
            if (is_link($target)) {
                unlink($target);
            } elseif (is_dir($target)) {
                $this->removeDirectory($target);
            }

            // Create symlink
            $result = symlink($source, $target);

            if (!$result) {
                $this->logger->warning("Failed to create symlink from $source to $target");
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->warning("Symlink creation failed: {$e->getMessage()}. Falling back to copy.");
            return false;
        }
    }

    /**
     * Copy assets from source to target directory.
     *
     * @param string $source Source directory path
     * @param string $target Target directory path
     */
    private function copyAssets(string $source, string $target): void
    {
        // Remove existing target directory
        if (is_dir($target)) {
            $this->removeDirectory($target);
        }

        // Create target directory
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        // Recursively copy files
        $directoryIterator = new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::SELF_FIRST
        );

        $sourceLen = strlen($source) + 1; // +1 for directory separator

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), $sourceLen);
            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Recursively remove a directory and its contents.
     *
     * @param string $dir Directory path to remove
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
