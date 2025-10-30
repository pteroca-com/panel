<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

class BalanceWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'balance';
    }

    public function getDisplayName(): string
    {
        return 'Account Balance';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::DASHBOARD];
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

    public function getData(WidgetContext $context, array $contextData): array
    {
        // Template uses app.user directly, no additional data needed
        return [];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::DASHBOARD;
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within RIGHT position
    }
}
