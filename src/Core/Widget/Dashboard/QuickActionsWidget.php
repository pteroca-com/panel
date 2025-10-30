<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

class QuickActionsWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'quick_actions';
    }

    public function getDisplayName(): string
    {
        return 'Quick Actions';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::DASHBOARD];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::TOP;
    }

    public function getPriority(): int
    {
        return 100; // High priority - show at very top
    }

    public function getTemplate(): string
    {
        return 'panel/dashboard/components/quick_actions.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        // No additional data needed - links are defined in template
        return [];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::DASHBOARD;
    }

    public function getColumnSize(): int
    {
        return 12; // Full width in TOP position
    }
}
