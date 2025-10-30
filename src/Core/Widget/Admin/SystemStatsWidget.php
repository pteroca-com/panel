<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * System statistics widget for admin overview.
 *
 * Displays key metrics:
 * - Active servers count
 * - Users registered in last 30 days
 * - Payments created in last 30 days
 * - Support links (Documentation, Discord)
 */
class SystemStatsWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'admin_system_stats';
    }

    public function getDisplayName(): string
    {
        return 'System Statistics';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::ADMIN_OVERVIEW];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::TOP;
    }

    public function getPriority(): int
    {
        return 100; // High priority - show first
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/system_stats.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $statistics = $contextData['statistics'] ?? [];

        return [
            'activeServers' => $statistics['activeServers'] ?? 0,
            'usersRegisteredLastMonth' => $statistics['usersRegisteredLastMonth'] ?? 0,
            'paymentsCreatedLastMonth' => $statistics['paymentsCreatedLastMonth'] ?? 0,
        ];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::ADMIN_OVERVIEW
            && isset($contextData['statistics']);
    }

    public function getColumnSize(): int
    {
        return 12; // Full width in TOP position
    }
}
