<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Manages Composer dependencies for plugins.
 *
 * This service handles installation, validation, and verification
 * of Composer dependencies in isolated per-plugin vendor directories.
 */
readonly class ComposerDependencyManager
{
    public function __construct(
        private string $projectDir,
        private LoggerInterface $logger
    ) {}

    /**
     * Install Composer dependencies for a plugin.
     *
     * Executes composer install with production flags:
     * - --no-dev (exclude development dependencies)
     * - --no-interaction (no prompts)
     * - --no-plugins (disable Composer plugins for security)
     * - --no-scripts (disable scripts for security)
     * - --prefer-dist (use distribution packages)
     * - --classmap-authoritative (optimize autoloader)
     *
     * @param Plugin $plugin Plugin to install dependencies for
     * @param bool $clean Remove vendor/ directory before installation
     * @throws \RuntimeException If installation fails
     */
    public function installDependencies(Plugin $plugin, bool $clean = false): void
    {
        $pluginPath = $this->getPluginPath($plugin);
        $vendorPath = $pluginPath . '/vendor';

        // Clean install - remove existing vendor directory
        if ($clean && is_dir($vendorPath)) {
            $this->logger->info('Removing existing vendor directory', [
                'plugin' => $plugin->getName(),
                'path' => $vendorPath,
            ]);

            $this->removeDirectory($vendorPath);
        }

        $this->logger->info('Installing Composer dependencies', [
            'plugin' => $plugin->getName(),
            'clean' => $clean,
        ]);

        // Execute composer install with production and security flags
        $process = new Process(
            [
                'composer',
                'install',
                '--no-dev',              // Exclude development dependencies
                '--no-interaction',      // No prompts
                '--no-progress',         // No progress bar (cleaner logs)
                '--no-plugins',          // Disable Composer plugins (security)
                '--no-scripts',          // Disable scripts (security)
                '--prefer-dist',         // Use distribution packages
                '--classmap-authoritative', // Optimize autoloader
            ],
            $pluginPath,
            null,
            null,
            300  // 5 minute timeout
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('Failed to install Composer dependencies', [
                'plugin' => $plugin->getName(),
                'exit_code' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new \RuntimeException(
                sprintf(
                    'Failed to install Composer dependencies for plugin "%s": %s',
                    $plugin->getName(),
                    $this->truncateOutput($process->getErrorOutput())
                )
            );
        }

        $this->logger->info('Composer dependencies installed successfully', [
            'plugin' => $plugin->getName(),
            'output' => $process->getOutput(),
        ]);
    }

    /**
     * Check if plugin has composer.json file.
     */
    public function hasComposerJson(Plugin $plugin): bool
    {
        $composerJsonPath = $this->getPluginPath($plugin) . '/composer.json';
        return file_exists($composerJsonPath);
    }

    /**
     * Check if plugin has composer.lock file.
     *
     * composer.lock is required for reproducible builds and security.
     */
    public function hasComposerLock(Plugin $plugin): bool
    {
        $composerLockPath = $this->getPluginPath($plugin) . '/composer.lock';
        return file_exists($composerLockPath);
    }

    /**
     * Check if plugin has installed vendor directory.
     */
    public function hasVendorDirectory(Plugin $plugin): bool
    {
        $vendorPath = $this->getPluginPath($plugin) . '/vendor';
        return is_dir($vendorPath);
    }

    /**
     * Validate Composer files (composer.json and composer.lock).
     *
     * Returns array of validation issues. Empty array means all checks passed.
     *
     * @return array<array{type: string, severity: string, message: string, file?: string}>
     */
    public function validateComposerFiles(Plugin $plugin): array
    {
        $issues = [];
        $pluginPath = $this->getPluginPath($plugin);

        // Check if composer.json exists
        if (!$this->hasComposerJson($plugin)) {
            return []; // No composer.json = no validation needed
        }

        // Check if composer.lock exists
        if (!$this->hasComposerLock($plugin)) {
            $issues[] = [
                'type' => 'composer_lock_missing',
                'severity' => 'HIGH',
                'message' => 'Plugin has composer.json but no composer.lock file. Run "composer install" locally and commit composer.lock.',
                'file' => 'composer.lock',
            ];
        }

        // Validate composer.json structure
        $process = new Process(
            ['composer', 'validate', '--no-check-publish', '--strict'],
            $pluginPath
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $issues[] = [
                'type' => 'composer_validate_failed',
                'severity' => 'HIGH',
                'message' => 'composer.json validation failed',
                'file' => 'composer.json',
                'details' => $this->truncateOutput($process->getErrorOutput()),
            ];
        }

        return $issues;
    }

    /**
     * Get installed packages from vendor/composer/installed.php.
     *
     * @return array<string, mixed> Installed package information
     */
    public function getInstalledPackages(Plugin $plugin): array
    {
        $installedPhpPath = $this->getPluginPath($plugin) . '/vendor/composer/installed.php';

        if (!file_exists($installedPhpPath)) {
            return [];
        }

        $installed = include $installedPhpPath;

        return $installed['versions'] ?? [];
    }

    /**
     * Get plugin absolute path.
     */
    private function getPluginPath(Plugin $plugin): string
    {
        return $this->projectDir . '/plugins/' . $plugin->getName();
    }

    /**
     * Truncate output for error messages (keep first 500 chars).
     */
    private function truncateOutput(string $output): string
    {
        $maxLength = 500;

        if (strlen($output) > $maxLength) {
            return substr($output, 0, $maxLength) . '... (truncated)';
        }

        return $output;
    }

    /**
     * Recursively remove directory.
     */
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $path . '/' . $file;

            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($path);
    }
}
