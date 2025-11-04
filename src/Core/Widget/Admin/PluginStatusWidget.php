<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use App\Core\Repository\PluginRepository;

/**
 * Plugin status widget for admin overview.
 * Displays statistics about plugins: total, enabled, disabled, faulted.
 */
readonly class PluginStatusWidget implements WidgetInterface
{
    public function __construct(
        private PluginRepository $pluginRepository,
    ) {}

    public function getName(): string
    {
        return 'admin_plugin_status';
    }

    public function getDisplayName(): string
    {
        return 'Plugin Status';
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
        return 90; // After system stats
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/plugin_status.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $statistics = $this->pluginRepository->getStateStatistics();

        return [
            'total' => array_sum($statistics),
            'enabled' => $statistics['enabled'] ?? 0,
            'disabled' => $statistics['disabled'] ?? 0,
            'faulted' => $statistics['faulted'] ?? 0,
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
