<?php

namespace App\Core\Service\Plugin;

use App\Core\DTO\PluginManifestDTO;
use App\Core\Trait\PluginDirectoryScannerTrait;
use Exception;
use Psr\Log\LoggerInterface;

class PluginScanner
{
    use PluginDirectoryScannerTrait;

    private const MANIFEST_FILENAME = 'plugin.json';

    private string $pluginsDirectory;
    private ManifestParser $manifestParser;
    private ManifestValidator $manifestValidator;
    private LoggerInterface $logger;

    public function __construct(
        string $pluginsDirectory,
        ManifestParser $manifestParser,
        ManifestValidator $manifestValidator,
        LoggerInterface $logger
    ) {
        $this->pluginsDirectory = rtrim($pluginsDirectory, '/');
        $this->manifestParser = $manifestParser;
        $this->manifestValidator = $manifestValidator;
        $this->logger = $logger;
    }

    /**
     * @return array<string, array{path: string, manifest: PluginManifestDTO, errors: array}> Map of plugin name => plugin data
     */
    public function scan(): array
    {
        if (!is_dir($this->pluginsDirectory)) {
            $this->logger->warning("Plugins directory does not exist: $this->pluginsDirectory");

            return [];
        }

        $discovered = [];

        // Use trait to scan plugin directories (don't parse yet)
        $rawPlugins = $this->scanPluginDirectory($this->pluginsDirectory, false);

        foreach ($rawPlugins as $rawPlugin) {
            $pluginPath = $rawPlugin['path'];

            // Try to discover and validate plugin
            $pluginData = $this->discoverPlugin($pluginPath);

            if ($pluginData !== null) {
                $pluginName = $pluginData['manifest']->name;
                $discovered[$pluginName] = $pluginData;

                $this->logger->info("Discovered plugin: $pluginName at $pluginPath");
            }
        }

        return $discovered;
    }

    /**
     * @return array{path: string, manifest: PluginManifestDTO, errors: array}|null Plugin data or null if not a valid plugin
     */
    public function discoverPlugin(string $pluginPath): ?array
    {
        $manifestPath = $pluginPath . '/' . self::MANIFEST_FILENAME;

        // Check if plugin.json exists
        if (!file_exists($manifestPath)) {
            $this->logger->debug("No manifest found in: $pluginPath");

            return null;
        }

        try {
            // Parse manifest
            $manifest = $this->manifestParser->parseFromDirectory($pluginPath);

            // Validate manifest
            $errors = $this->manifestValidator->validate($manifest);

            return [
                'path' => $pluginPath,
                'manifest' => $manifest,
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            $this->logger->error("Failed to parse plugin manifest in $pluginPath: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * @return array<string, array{path: string, manifest: PluginManifestDTO}> Map of plugin name => plugin data
     */
    public function scanValid(): array
    {
        $allPlugins = $this->scan();
        $validPlugins = [];

        foreach ($allPlugins as $name => $data) {
            if (count($data['errors']) === 0) {
                $validPlugins[$name] = [
                    'path' => $data['path'],
                    'manifest' => $data['manifest'],
                ];
            } else {
                $errorMessages = implode(', ', $data['errors']);
                $this->logger->warning("Plugin '$name' has validation errors: $errorMessages");
            }
        }

        return $validPlugins;
    }

    /**
     * @return array<string, array{path: string, manifest: PluginManifestDTO, errors: array}> Map of plugin name => plugin data
     */
    public function scanInvalid(): array
    {
        $allPlugins = $this->scan();

        return array_filter($allPlugins, function ($data) {
            return count($data['errors']) > 0;
        });
    }

    public function exists(string $pluginName): bool
    {
        $pluginPath = $this->pluginsDirectory . '/' . $pluginName;

        if (!is_dir($pluginPath)) {
            return false;
        }

        $manifestPath = $pluginPath . '/' . self::MANIFEST_FILENAME;

        return file_exists($manifestPath);
    }

    public function getPluginPath(string $pluginName): string
    {
        return $this->pluginsDirectory . '/' . $pluginName;
    }

    public function getPluginsDirectory(): string
    {
        return $this->pluginsDirectory;
    }

    public function count(): int
    {
        return count($this->scan());
    }

    public function countValid(): int
    {
        return count($this->scanValid());
    }

    public function countInvalid(): int
    {
        return count($this->scanInvalid());
    }

    /**
     * @return array{total: int, valid: int, invalid: int} Summary statistics
     */
    public function getSummary(): array
    {
        $all = $this->scan();
        $valid = 0;
        $invalid = 0;

        foreach ($all as $data) {
            if (count($data['errors']) === 0) {
                ++$valid;
            } else {
                ++$invalid;
            }
        }

        return [
            'total' => count($all),
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }
}
