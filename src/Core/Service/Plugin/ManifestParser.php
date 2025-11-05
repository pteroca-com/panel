<?php

namespace App\Core\Service\Plugin;

use App\Core\DTO\PluginManifestDTO;
use Exception;
use JsonException;
use RuntimeException;

class ManifestParser
{
    public function parseFromDirectory(string $pluginPath): PluginManifestDTO
    {
        $manifestPath = rtrim($pluginPath, '/') . '/plugin.json';

        if (!file_exists($manifestPath)) {
            throw new RuntimeException("Plugin manifest not found: $manifestPath");
        }

        $manifestContent = file_get_contents($manifestPath);

        if ($manifestContent === false) {
            throw new RuntimeException("Failed to read plugin manifest: $manifestPath");
        }

        return $this->parseFromString($manifestContent);
    }

    /**
     * @throws RuntimeException If JSON is invalid
     */
    public function parseFromString(string $json): PluginManifestDTO
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON in plugin manifest: {$e->getMessage()}", 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Plugin manifest must be a JSON object');
        }

        return $this->parseFromArray($data);
    }

    /**
     * @throws RuntimeException If required fields are missing
     */
    public function parseFromArray(array $data): PluginManifestDTO
    {
        // Extract required fields (will throw if missing)
        $name = $this->getRequiredField($data, 'name');
        $displayName = $this->getRequiredField($data, 'display_name');
        $version = $this->getRequiredField($data, 'version');
        $author = $this->getRequiredField($data, 'author');
        $description = $this->getRequiredField($data, 'description');
        $license = $this->getRequiredField($data, 'license');
        $pteroca = $this->getRequiredField($data, 'pteroca');
        $capabilities = $this->getRequiredField($data, 'capabilities');

        // Extract optional fields with defaults
        $requires = $data['requires'] ?? [];
        $configSchema = $data['config_schema'] ?? [];
        $marketplaceUrl = $data['marketplace_url'] ?? null;
        $bootstrapClass = $data['bootstrap_class'] ?? null;
        $migrations = $data['migrations'] ?? null;
        $routes = $data['routes'] ?? null;
        $console = $data['console'] ?? null;
        $cron = $data['cron'] ?? null;
        $assets = $data['assets'] ?? null;

        // Ensure arrays are arrays
        if (!is_array($pteroca)) {
            throw new RuntimeException('Field "pteroca" must be an object');
        }

        if (!is_array($capabilities)) {
            throw new RuntimeException('Field "capabilities" must be an array');
        }

        if (!is_array($requires)) {
            throw new RuntimeException('Field "requires" must be an object');
        }

        if (!is_array($configSchema)) {
            throw new RuntimeException('Field "config_schema" must be an object');
        }

        return new PluginManifestDTO(
            name: $name,
            displayName: $displayName,
            version: $version,
            author: $author,
            description: $description,
            license: $license,
            pteroca: $pteroca,
            capabilities: $capabilities,
            requires: $requires,
            configSchema: $configSchema,
            marketplaceUrl: $marketplaceUrl,
            bootstrapClass: $bootstrapClass,
            migrations: $migrations,
            routes: $routes,
            console: $console,
            cron: $cron,
            assets: $assets,
            raw: $data,
        );
    }

    /**
     * @throws RuntimeException If field is missing or empty
     */
    private function getRequiredField(array $data, string $field): mixed
    {
        if (!array_key_exists($field, $data)) {
            throw new RuntimeException("Required field '$field' is missing in plugin manifest");
        }

        $value = $data[$field];

        // Check for empty strings
        if (is_string($value) && trim($value) === '') {
            throw new RuntimeException("Required field '$field' cannot be empty");
        }

        return $value;
    }

    public function canParse(string $pluginPath): bool
    {
        try {
            $this->parseFromDirectory($pluginPath);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
