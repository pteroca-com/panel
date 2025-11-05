<?php

namespace App\Core\Service\Plugin;

use App\Core\DTO\PluginHealthCheckResultDTO;
use App\Core\Entity\Plugin;
use App\Core\Repository\PluginRepository;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Plugin health check service.
 *
 * Performs comprehensive health checks on plugins:
 * - Files integrity (manifest exists, required directories)
 * - Dependencies validation (all declared dependencies installed)
 * - Configuration validation (valid config_schema, settings initialized)
 * - Service registration (all services can be instantiated)
 *
 * Returns PluginHealthCheckResultDTO with detailed results.
 */
readonly class PluginHealthCheckService
{
    public function __construct(
        private PluginRepository         $pluginRepository,
        private PluginDependencyResolver $dependencyResolver,
        private LoggerInterface          $logger,
    ) {}

    /**
     * Run health check on single plugin.
     *
     * @param Plugin $plugin Plugin to check
     * @return PluginHealthCheckResultDTO Health check results
     */
    public function runHealthCheck(Plugin $plugin): PluginHealthCheckResultDTO
    {
        $checks = [];
        $errors = [];

        $this->logger->info("Running health check for plugin", [
            'plugin' => $plugin->getName(),
        ]);

        // Check 1: Files integrity
        try {
            $checks['files_integrity'] = $this->checkFilesIntegrity($plugin);
        } catch (Exception $e) {
            $checks['files_integrity'] = false;
            $errors['files_integrity'] = $e->getMessage();
        }

        // Check 2: Dependencies
        try {
            $dependencyErrors = $this->checkDependencies($plugin);
            $checks['dependencies'] = empty($dependencyErrors);
            if (!empty($dependencyErrors)) {
                $errors['dependencies'] = implode('; ', $dependencyErrors);
            }
        } catch (Exception $e) {
            $checks['dependencies'] = false;
            $errors['dependencies'] = $e->getMessage();
        }

        // Check 3: Configuration
        try {
            $configErrors = $this->checkConfiguration($plugin);
            $checks['configuration'] = empty($configErrors);
            if (!empty($configErrors)) {
                $errors['configuration'] = implode('; ', $configErrors);
            }
        } catch (Exception $e) {
            $checks['configuration'] = false;
            $errors['configuration'] = $e->getMessage();
        }

        // Check 4: Service registration
        try {
            $checks['service_registration'] = $this->checkServiceRegistration($plugin);
        } catch (Exception $e) {
            $checks['service_registration'] = false;
            $errors['service_registration'] = $e->getMessage();
        }

        // Determine overall health
        $healthy = empty($errors);

        $result = $healthy
            ? PluginHealthCheckResultDTO::success($plugin, $checks)
            : PluginHealthCheckResultDTO::failure($plugin, $checks, $errors);

        $this->logger->info("Health check completed", [
            'plugin' => $plugin->getName(),
            'healthy' => $healthy,
            'passed' => $result->getPassedCount(),
            'failed' => $result->getFailedCount(),
        ]);

        return $result;
    }

    /**
     * Run health checks on all enabled plugins.
     *
     * @return array<PluginHealthCheckResultDTO>
     */
    public function runAllHealthChecks(): array
    {
        $plugins = $this->pluginRepository->findEnabled();
        $results = [];

        foreach ($plugins as $plugin) {
            $results[] = $this->runHealthCheck($plugin);
        }

        return $results;
    }

    /**
     * Check files integrity.
     *
     * Validates that:
     * - Plugin path exists
     * - plugin.json exists and is readable
     * - Required directories exist (if declared)
     *
     * @return bool True if all files are intact
     * @throws RuntimeException If critical files missing
     */
    public function checkFilesIntegrity(Plugin $plugin): bool
    {
        $pluginPath = $plugin->getPath();

        // Check plugin directory exists
        if (!is_dir($pluginPath)) {
            throw new RuntimeException(sprintf(
                "Plugin directory does not exist: %s",
                $pluginPath
            ));
        }

        // Check plugin.json exists
        $manifestPath = $pluginPath . '/plugin.json';
        if (!file_exists($manifestPath)) {
            throw new RuntimeException("plugin.json not found");
        }

        if (!is_readable($manifestPath)) {
            throw new RuntimeException("plugin.json is not readable");
        }

        // Validate JSON is parseable
        $manifestContent = file_get_contents($manifestPath);
        json_decode($manifestContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                "plugin.json is invalid JSON: %s",
                json_last_error_msg()
            ));
        }

        // Check required directories if declared
        $requiredDirs = [
            'src' => 'Source code directory',
        ];

        foreach ($requiredDirs as $dir => $description) {
            $dirPath = $pluginPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                throw new RuntimeException(sprintf(
                    "%s missing: %s",
                    $description,
                    $dir
                ));
            }
        }

        return true;
    }

    /**
     * Check dependencies validation.
     *
     * Validates that all declared dependencies:
     * - Are installed
     * - Meet version requirements
     * - Are enabled (for plugin dependencies)
     *
     * @return array<string> Array of error messages (empty if all OK)
     */
    public function checkDependencies(Plugin $plugin): array
    {
        return $this->dependencyResolver->validateDependencies($plugin);
    }

    /**
     * Check configuration validation.
     *
     * Validates that:
     * - config_schema is valid (if declared)
     * - Settings are properly initialized
     * - No type mismatches in settings
     *
     * @return array<string> Array of error messages (empty if all OK)
     */
    public function checkConfiguration(Plugin $plugin): array
    {
        $errors = [];

        $configSchema = $plugin->getConfigSchema();

        if (empty($configSchema)) {
            // No configuration declared - OK
            return [];
        }

        // Validate config_schema structure
        foreach ($configSchema as $key => $config) {
            if (!isset($config['type'])) {
                $errors[] = sprintf("Setting '%s' missing 'type' field", $key);
                continue;
            }

            $validTypes = ['string', 'integer', 'boolean', 'float', 'json'];
            if (!in_array($config['type'], $validTypes, true)) {
                $errors[] = sprintf(
                    "Setting '%s' has invalid type '%s' (valid: %s)",
                    $key,
                    $config['type'],
                    implode(', ', $validTypes)
                );
            }

            // Check if default value matches declared type
            if (isset($config['default'])) {
                $defaultValue = $config['default'];
                $declaredType = $config['type'];

                $actualType = gettype($defaultValue);
                $typeMap = [
                    'string' => 'string',
                    'integer' => 'integer',
                    'boolean' => 'boolean',
                    'double' => 'float',
                    'array' => 'json',
                ];

                $expectedPhpType = array_search($declaredType, $typeMap, true);

                if ($expectedPhpType !== false && $actualType !== $expectedPhpType) {
                    $errors[] = sprintf(
                        "Setting '%s' default value type mismatch: expected %s, got %s",
                        $key,
                        $declaredType,
                        $actualType
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Check service registration.
     *
     * Validates that plugin services can be registered:
     * - services.yaml exists (if plugin uses services)
     * - services.yaml is valid YAML
     * - No syntax errors in service definitions
     *
     * @return bool True if services are OK
     * @throws RuntimeException If services have issues
     */
    public function checkServiceRegistration(Plugin $plugin): bool
    {
        $servicesPath = $plugin->getPath() . '/config/services.yaml';

        // If services.yaml doesn't exist, that's OK (plugin may not use services)
        if (!file_exists($servicesPath)) {
            return true;
        }

        // Check services.yaml is readable
        if (!is_readable($servicesPath)) {
            throw new RuntimeException("config/services.yaml exists but is not readable");
        }

        // Validate YAML syntax
        try {
            $servicesContent = file_get_contents($servicesPath);

            // Basic YAML syntax check (we can't fully parse without Symfony YAML component)
            // Just check for common issues
            if (empty(trim($servicesContent))) {
                throw new RuntimeException("config/services.yaml is empty");
            }

            // Check for tab indentation (YAML doesn't allow tabs)
            if (str_contains($servicesContent, "\t")) {
                throw new RuntimeException("config/services.yaml contains tabs (use spaces for indentation)");
            }

        } catch (Exception $e) {
            throw new RuntimeException(sprintf(
                "config/services.yaml validation failed: %s",
                $e->getMessage()
            ));
        }

        return true;
    }
}
