<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use App\Core\Repository\PluginRepository;
use App\Core\Service\Plugin\PluginHealthCheckService;

/**
 * Plugin health widget for admin overview.
 * Displays health check results for enabled plugins.
 */
readonly class PluginHealthWidget implements WidgetInterface
{
    public function __construct(
        private PluginRepository         $pluginRepository,
        private PluginHealthCheckService $healthCheckService,
    ) {}

    public function getName(): string
    {
        return 'admin_plugin_health';
    }

    public function getDisplayName(): string
    {
        return 'Plugin Health';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::ADMIN_OVERVIEW];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::BOTTOM;
    }

    public function getPriority(): int
    {
        return 45; // Between security and status
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/plugin_health.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $enabledPlugins = $this->pluginRepository->findEnabled();
        $healthyCount = 0;
        $unhealthyCount = 0;
        $unhealthyPlugins = [];
        $lastChecked = null;

        foreach ($enabledPlugins as $plugin) {
            $result = $this->healthCheckService->runHealthCheck($plugin);

            if ($result->healthy) {
                $healthyCount++;
            } else {
                $unhealthyCount++;
                $unhealthyPlugins[] = [
                    'name' => $plugin->getDisplayName(),
                    'health_percentage' => (int) $result->getHealthPercentage(),
                    'failed_checks' => $result->getFailedCount(),
                ];
            }

            if ($lastChecked === null || $result->checkedAt > $lastChecked) {
                $lastChecked = $result->checkedAt;
            }
        }

        return [
            'healthy_count' => $healthyCount,
            'unhealthy_count' => $unhealthyCount,
            'unhealthy_plugins' => $unhealthyPlugins,
            'last_checked' => $lastChecked?->format('Y-m-d H:i:s'),
        ];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::ADMIN_OVERVIEW;
    }

    public function getColumnSize(): int
    {
        return 6; // Half width
    }
}
