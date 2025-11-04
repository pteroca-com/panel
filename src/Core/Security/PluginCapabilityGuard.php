<?php

namespace App\Core\Security;

use App\Core\Entity\Plugin;
use App\Core\Exception\Plugin\PluginCapabilityException;
use App\Core\Repository\PluginRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Runtime capability enforcement for plugins.
 *
 * Validates that plugins have required capabilities before performing actions.
 * Supports two enforcement modes:
 * - 'soft': Log warning but allow action (backward compatible)
 * - 'strict': Throw exception and block action
 *
 * Enforcement mode is configured via PLUGIN_CAPABILITY_ENFORCEMENT env variable.
 */
class PluginCapabilityGuard
{
    private const ENFORCEMENT_MODE_SOFT = 'soft';
    private const ENFORCEMENT_MODE_STRICT = 'strict';

    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'plugin_security.enforcement_mode')]
        private readonly string $enforcementMode = self::ENFORCEMENT_MODE_SOFT,
    ) {}

    /**
     * Check if plugin has required capability (non-throwing).
     *
     * @param Plugin $plugin Plugin to check
     * @param string $capability Required capability
     * @return bool True if plugin has capability
     */
    public function checkCapability(Plugin $plugin, string $capability): bool
    {
        return $plugin->hasCapability($capability);
    }

    /**
     * Require plugin to have capability - throws exception if missing.
     *
     * Behavior depends on enforcement mode:
     * - soft: Logs warning and returns (backward compatible)
     * - strict: Throws PluginCapabilityException
     *
     * @param Plugin $plugin Plugin to check
     * @param string $capability Required capability
     * @param string $operation Description of operation (for error message)
     * @throws PluginCapabilityException If capability missing and strict mode
     */
    public function requireCapability(Plugin $plugin, string $capability, string $operation): void
    {
        if ($plugin->hasCapability($capability)) {
            return;
        }

        $logContext = [
            'plugin' => $plugin->getName(),
            'capability' => $capability,
            'operation' => $operation,
            'mode' => $this->enforcementMode,
        ];

        if ($this->enforcementMode === self::ENFORCEMENT_MODE_SOFT) {
            $this->logger->warning(
                sprintf(
                    "Plugin '%s' missing capability '%s' for operation: %s (soft mode - allowing)",
                    $plugin->getName(),
                    $capability,
                    $operation
                ),
                $logContext
            );
            return;
        }

        // Strict mode - throw exception
        $this->logger->error(
            sprintf(
                "Plugin '%s' blocked - missing capability '%s' for operation: %s",
                $plugin->getName(),
                $capability,
                $operation
            ),
            $logContext
        );

        throw PluginCapabilityException::missingCapability(
            $plugin->getName(),
            $capability,
            $operation
        );
    }

    /**
     * Validate plugin access for specific action.
     *
     * @param string $pluginName Plugin name
     * @param string $action Action to validate
     * @return bool True if access granted
     */
    public function validatePluginAccess(string $pluginName, string $action): bool
    {
        $plugin = $this->pluginRepository->findByName($pluginName);

        if ($plugin === null) {
            $this->logger->warning("Access validation failed: plugin not found", [
                'plugin' => $pluginName,
                'action' => $action,
            ]);
            return false;
        }

        // Map actions to required capabilities
        $requiredCapability = $this->mapActionToCapability($action);

        if ($requiredCapability === null) {
            // Action doesn't require specific capability
            return true;
        }

        if (!$plugin->hasCapability($requiredCapability)) {
            $this->logger->warning("Access validation failed: missing capability", [
                'plugin' => $pluginName,
                'action' => $action,
                'required_capability' => $requiredCapability,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get current enforcement mode.
     *
     * @return string 'soft' or 'strict'
     */
    public function getEnforcementMode(): string
    {
        return $this->enforcementMode;
    }

    /**
     * Check if enforcement mode is strict.
     */
    public function isStrictMode(): bool
    {
        return $this->enforcementMode === self::ENFORCEMENT_MODE_STRICT;
    }

    /**
     * Map action to required capability.
     *
     * @param string $action Action identifier
     * @return string|null Required capability or null if no specific capability needed
     */
    private function mapActionToCapability(string $action): ?string
    {
        return match ($action) {
            'register_routes' => 'routes',
            'register_widgets' => 'widgets',
            'register_entities', 'run_migrations' => 'entities',
            'register_commands' => 'console',
            'register_cron_tasks' => 'cron',
            'register_event_subscribers' => 'eda',
            'access_translations' => 'translations',
            'access_assets' => 'assets',
            default => null,
        };
    }
}
