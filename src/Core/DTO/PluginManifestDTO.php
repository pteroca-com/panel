<?php

namespace App\Core\DTO;

/**
 * Data Transfer Object for plugin manifest (plugin.json).
 *
 * Represents a parsed and validated plugin.json file with all required
 * and optional fields defined in the plugin manifest schema.
 */
readonly class PluginManifestDTO
{
    /**
     * @param string $name Plugin system identifier (e.g., 'hello-world', 'acme-payments')
     * @param string $displayName Human-readable plugin name
     * @param string $version Semantic version (e.g., '1.0.0')
     * @param string $author Author name or organization
     * @param string $description Plugin description
     * @param string $license License identifier (SPDX format)
     * @param array $pteroca PteroCA version requirements ['min' => '0.5.9', 'max' => '1.0.0']
     * @param array<string> $capabilities List of capability identifiers
     * @param array<string, string> $requires Plugin dependencies (plugin_name => version_constraint)
     * @param array $configSchema Configuration schema for settings
     * @param string|null $marketplaceUrl Optional marketplace URL
     * @param string|null $bootstrapClass Optional bootstrap class name
     * @param array|null $migrations Optional migrations configuration
     * @param array|null $routes Optional routes configuration
     * @param array|null $console Optional console commands configuration
     * @param array|null $cron Optional cron tasks configuration
     * @param array|null $assets Optional assets configuration (css, js, img, fonts)
     * @param array $raw Raw manifest data (full plugin.json content)
     */
    public function __construct(
        public string $name,
        public string $displayName,
        public string $version,
        public string $author,
        public string $description,
        public string $license,
        public array $pteroca,
        public array $capabilities,
        public array $requires = [],
        public array $configSchema = [],
        public ?string $marketplaceUrl = null,
        public ?string $bootstrapClass = null,
        public ?array $migrations = null,
        public ?array $routes = null,
        public ?array $console = null,
        public ?array $cron = null,
        public ?array $assets = null,
        public array $raw = [],
    ) {}

    /**
     * Get minimum required PteroCA version.
     */
    public function getMinPterocaVersion(): string
    {
        return $this->pteroca['min'] ?? '0.0.0';
    }

    /**
     * Get maximum compatible PteroCA version.
     */
    public function getMaxPterocaVersion(): ?string
    {
        return $this->pteroca['max'] ?? null;
    }

    /**
     * Check if plugin has specific capability.
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * Check if plugin has any dependencies.
     */
    public function hasDependencies(): bool
    {
        return count($this->requires) > 0;
    }

    /**
     * Get list of required plugin names.
     *
     * @return string[]
     */
    public function getRequiredPluginNames(): array
    {
        return array_keys($this->requires);
    }

    /**
     * Get version constraint for a specific dependency.
     */
    public function getDependencyConstraint(string $pluginName): ?string
    {
        return $this->requires[$pluginName] ?? null;
    }

    /**
     * Get migrations namespace (if migrations capability is enabled).
     */
    public function getMigrationsNamespace(): ?string
    {
        return $this->migrations['namespace'] ?? null;
    }

    /**
     * Get migrations directory (if migrations capability is enabled).
     */
    public function getMigrationsDirectory(): ?string
    {
        return $this->migrations['directory'] ?? null;
    }

    /**
     * Get table prefix for plugin entities (shortname for database tables).
     */
    public function getTablePrefix(): string
    {
        // Extract shortname from plugin name (e.g., 'acme-payments' -> 'acme_payments')
        // Plugin entities should use 'plg_{shortname}_*' pattern
        $shortname = str_replace('-', '_', $this->name);

        return "plg_{$shortname}_";
    }

    /**
     * Check if plugin has any assets defined.
     */
    public function hasAssets(): bool
    {
        return $this->assets !== null && count($this->assets) > 0;
    }

    /**
     * Get assets for a specific type (css, js, img, fonts).
     *
     * @param string $type Asset type (css, js, img, fonts)
     * @return string[] Array of asset paths
     */
    public function getAssets(string $type): array
    {
        return $this->assets[$type] ?? [];
    }

    /**
     * Get all CSS assets.
     *
     * @return string[]
     */
    public function getCssAssets(): array
    {
        return $this->getAssets('css');
    }

    /**
     * Get all JS assets.
     *
     * @return string[]
     */
    public function getJsAssets(): array
    {
        return $this->getAssets('js');
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'license' => $this->license,
            'pteroca' => $this->pteroca,
            'capabilities' => $this->capabilities,
            'requires' => $this->requires,
            'config_schema' => $this->configSchema,
            'marketplace_url' => $this->marketplaceUrl,
            'bootstrap_class' => $this->bootstrapClass,
            'migrations' => $this->migrations,
            'routes' => $this->routes,
            'console' => $this->console,
            'cron' => $this->cron,
            'assets' => $this->assets,
        ];
    }
}
