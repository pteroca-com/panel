<?php

namespace App\Core\Service\Plugin;

use App\Core\Contract\UserInterface;
use App\Core\DTO\PluginManifestDTO;
use App\Core\Entity\Plugin;
use App\Core\Enum\LogActionEnum;
use App\Core\Service\Logs\LogService;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Plugin audit logging service.
 *
 * Provides specialized logging for plugin operations with support for:
 * - Database logging via LogService (user-associated actions)
 * - File logging via Monolog plugin channel
 * - Optional user context (some actions are system-initiated)
 */
readonly class PluginAuditLogger
{
    public function __construct(
        private LogService      $logService,
        #[Autowire(service: 'monolog.logger.plugin')]
        private LoggerInterface $pluginLogger,
    ) {}

    /**
     * Log generic plugin action.
     *
     * @param Plugin $plugin Plugin instance
     * @param LogActionEnum $action Action type
     * @param UserInterface|null $user User who performed the action (null for system actions)
     * @param array $context Additional context data
     */
    public function logPluginAction(
        Plugin $plugin,
        LogActionEnum $action,
        ?UserInterface $user = null,
        array $context = []
    ): void {
        $details = array_merge([
            'plugin_name' => $plugin->getName(),
            'plugin_display_name' => $plugin->getDisplayName(),
            'plugin_version' => $plugin->getVersion(),
            'plugin_state' => $plugin->getState()->value,
        ], $context);

        // Log to database if user is available
        if ($user !== null) {
            $this->logService->logAction($user, $action, $details);
        }

        // Always log to plugin.log file
        $this->pluginLogger->info(
            sprintf('Plugin action: %s - %s', $action->name, $plugin->getName()),
            [
                'action' => $action->name,
                'plugin' => $plugin->getName(),
                'user' => $user?->getEmail() ?? 'system',
                'context' => $context,
            ]
        );
    }

    /**
     * Log plugin enabled event.
     */
    public function logPluginEnabled(Plugin $plugin, ?UserInterface $user): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_ENABLED,
            $user,
            [
                'enabled_at' => $plugin->getEnabledAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Log plugin disabled event.
     */
    public function logPluginDisabled(Plugin $plugin, ?UserInterface $user): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_DISABLED,
            $user,
            [
                'disabled_at' => $plugin->getDisabledAt()?->format('Y-m-d H:i:s'),
                'enabled_duration' => $this->calculateEnabledDuration($plugin),
            ]
        );
    }

    /**
     * Log plugin discovered event (system action, no user).
     *
     * Accepts either a Plugin entity (after registration) or path + manifest data (during discovery).
     */
    public function logPluginDiscovered(Plugin|string $pluginOrPath, ?PluginManifestDTO $manifest = null): void
    {
        if ($pluginOrPath instanceof Plugin) {
            // Plugin entity provided (already registered)
            $this->logPluginAction(
                $pluginOrPath,
                LogActionEnum::PLUGIN_DISCOVERED,
                null,
                [
                    'path' => $pluginOrPath->getPath(),
                    'pteroca_min_version' => $pluginOrPath->getPterocaMinVersion(),
                    'pteroca_max_version' => $pluginOrPath->getPterocaMaxVersion(),
                ]
            );
        } else {
            // Path + manifest provided (during discovery, before registration)
            if ($manifest === null) {
                throw new InvalidArgumentException('Manifest is required when providing plugin path');
            }

            $details = [
                'plugin_name' => $manifest->name,
                'plugin_display_name' => $manifest->displayName,
                'plugin_version' => $manifest->version,
                'path' => $pluginOrPath,
                'pteroca_min_version' => $manifest->getMinPterocaVersion(),
                'pteroca_max_version' => $manifest->getMaxPterocaVersion(),
            ];

            // Log to plugin.log file only (no database log as plugin isn't registered yet)
            $this->pluginLogger->info(
                sprintf('Plugin action: %s - %s', LogActionEnum::PLUGIN_DISCOVERED->name, $manifest->name),
                [
                    'action' => LogActionEnum::PLUGIN_DISCOVERED->name,
                    'plugin' => $manifest->name,
                    'user' => 'system',
                    'context' => $details,
                ]
            );
        }
    }

    /**
     * Log plugin registered event (system action, no user).
     */
    public function logPluginRegistered(Plugin $plugin): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_REGISTERED,
            null,
            [
                'author' => $plugin->getAuthor(),
                'license' => $plugin->getLicense(),
            ]
        );
    }

    /**
     * Log plugin updated event.
     */
    public function logPluginUpdated(Plugin $plugin, string $oldVersion, ?UserInterface $user): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_UPDATED,
            $user,
            [
                'old_version' => $oldVersion,
                'new_version' => $plugin->getVersion(),
            ]
        );
    }

    /**
     * Log plugin faulted event.
     */
    public function logPluginFaulted(Plugin $plugin, string $reason, ?UserInterface $user = null): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_FAULTED,
            $user,
            [
                'fault_reason' => $reason,
                'faulted_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );

        // Also log as error to Monolog
        $this->pluginLogger->error(
            sprintf('Plugin faulted: %s - %s', $plugin->getName(), $reason),
            [
                'plugin' => $plugin->getName(),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log plugin configuration changed.
     */
    public function logPluginConfigChanged(
        Plugin $plugin,
        string $key,
        mixed $oldValue,
        mixed $newValue,
        UserInterface $user
    ): void {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_SETTING_CHANGED,
            $user,
            [
                'setting_key' => $key,
                'old_value' => $this->sanitizeValue($oldValue),
                'new_value' => $this->sanitizeValue($newValue),
            ]
        );
    }

    /**
     * Log plugin migration executed.
     */
    public function logPluginMigration(
        Plugin $plugin,
        string $migrationName,
        bool $success,
        ?string $errorMessage = null
    ): void {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_MIGRATION_EXECUTED,
            null,
            [
                'migration' => $migrationName,
                'success' => $success,
                'error' => $errorMessage,
            ]
        );

        if (!$success && $errorMessage !== null) {
            $this->pluginLogger->error(
                sprintf('Plugin migration failed: %s - %s', $plugin->getName(), $migrationName),
                [
                    'plugin' => $plugin->getName(),
                    'migration' => $migrationName,
                    'error' => $errorMessage,
                ]
            );
        }
    }

    /**
     * Log plugin asset published.
     */
    public function logPluginAssetPublished(Plugin $plugin, array $assets, UserInterface $user): void
    {
        $this->logPluginAction(
            $plugin,
            LogActionEnum::PLUGIN_ASSET_PUBLISHED,
            $user,
            [
                'assets_count' => count($assets),
                'assets' => $assets,
            ]
        );
    }

    /**
     * Log plugin error (exception/throwable).
     */
    public function logPluginError(Plugin $plugin, Throwable $error, ?UserInterface $user = null): void
    {
        $this->pluginLogger->error(
            sprintf('Plugin error: %s - %s', $plugin->getName(), $error->getMessage()),
            [
                'plugin' => $plugin->getName(),
                'error_class' => $error::class,
                'error_message' => $error->getMessage(),
                'error_file' => $error->getFile(),
                'error_line' => $error->getLine(),
                'stack_trace' => $error->getTraceAsString(),
                'user' => $user?->getEmail() ?? 'system',
            ]
        );
    }

    /**
     * Calculate how long plugin was enabled (in seconds).
     */
    private function calculateEnabledDuration(Plugin $plugin): ?int
    {
        $enabledAt = $plugin->getEnabledAt();
        $disabledAt = $plugin->getDisabledAt();

        if ($enabledAt === null || $disabledAt === null) {
            return null;
        }

        return $disabledAt->getTimestamp() - $enabledAt->getTimestamp();
    }

    /**
     * Sanitize value for logging (hide sensitive data, limit size).
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value) && strlen($value) > 500) {
            return substr($value, 0, 500) . '... (truncated)';
        }

        if (is_array($value)) {
            return array_map(fn($v) => $this->sanitizeValue($v), $value);
        }

        return $value;
    }
}
