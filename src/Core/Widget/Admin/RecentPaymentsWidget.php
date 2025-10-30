<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * Recent payments widget for admin overview.
 *
 * Displays a table of the most recent payments (last 5).
 */
class RecentPaymentsWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'admin_recent_payments';
    }

    public function getDisplayName(): string
    {
        return 'Payment Overview';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::ADMIN_OVERVIEW];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::LEFT;
    }

    public function getPriority(): int
    {
        return 100; // High priority - show first in left column
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/recent_payments.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $statistics = $contextData['statistics'] ?? [];

        return [
            'lastPayments' => $statistics['lastPayments'] ?? [],
        ];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::ADMIN_OVERVIEW
            && isset($contextData['statistics']);
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within LEFT position
    }
}
