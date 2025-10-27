<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Contract\Widget\DashboardWidgetInterface;
use App\Core\Enum\WidgetPosition;

class QuickActionsWidget implements DashboardWidgetInterface
{
    public function getName(): string
    {
        return 'quick_actions';
    }

    public function getDisplayName(): string
    {
        return 'Quick Actions';
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

    public function getData(UserInterface $user): array
    {
        // No additional data needed - links are defined in template
        return [];
    }

    public function isVisible(UserInterface $user): bool
    {
        return true; // Always visible for authenticated users
    }

    public function getColumnSize(): int
    {
        return 12; // Full width in TOP position
    }
}
