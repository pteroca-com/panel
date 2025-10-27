<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Contract\Widget\DashboardWidgetInterface;
use App\Core\Enum\WidgetPosition;

class BalanceWidget implements DashboardWidgetInterface
{
    public function getName(): string
    {
        return 'balance';
    }

    public function getDisplayName(): string
    {
        return 'Account Balance';
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::RIGHT;
    }

    public function getPriority(): int
    {
        return 100; // High priority - show at top
    }

    public function getTemplate(): string
    {
        return 'panel/dashboard/components/balance.html.twig';
    }

    public function getData(UserInterface $user): array
    {
        // Template uses app.user directly, no additional data needed
        return [];
    }

    public function isVisible(UserInterface $user): bool
    {
        return true; // Always visible for authenticated users
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within RIGHT position
    }
}
