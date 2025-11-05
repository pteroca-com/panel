<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use App\Core\Repository\PluginRepository;
use App\Core\Service\Plugin\PluginSecurityValidator;

/**
 * Plugin security widget for admin overview.
 * Displays security scan results and warnings.
 */
readonly class PluginSecurityWidget implements WidgetInterface
{
    public function __construct(
        private PluginRepository        $pluginRepository,
        private PluginSecurityValidator $securityValidator,
    ) {}

    public function getName(): string
    {
        return 'admin_plugin_security';
    }

    public function getDisplayName(): string
    {
        return 'Plugin Security';
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
        return 40; // High priority for security
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/plugin_security.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $enabledPlugins = $this->pluginRepository->findEnabled();
        $totalIssues = 0;
        $criticalIssues = 0;
        $highIssues = 0;
        $affectedPlugins = [];

        foreach ($enabledPlugins as $plugin) {
            $securityCheckResult = $this->securityValidator->validate($plugin);

            if (!$securityCheckResult->secure) {
                $affectedPlugins[] = [
                    'name' => $plugin->getDisplayName(),
                    'issues_count' => $securityCheckResult->getTotalIssues(),
                ];

                $totalIssues += $securityCheckResult->getTotalIssues();
                $criticalIssues += $securityCheckResult->getCriticalCount();
                $highIssues += $securityCheckResult->getHighCount();
            }
        }

        return [
            'total_issues' => $totalIssues,
            'critical_issues' => $criticalIssues,
            'high_issues' => $highIssues,
            'affected_plugins' => $affectedPlugins,
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
