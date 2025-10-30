<?php

namespace App\Core\Widget\Admin;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * Recent users widget for admin overview.
 *
 * Displays a table of the most recently registered users (last 5).
 */
class RecentUsersWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'admin_recent_users';
    }

    public function getDisplayName(): string
    {
        return 'Last Registered Users';
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
        return 90; // Below recent payments (100), show second
    }

    public function getTemplate(): string
    {
        return 'panel/admin/widgets/recent_users.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        if ($context !== WidgetContext::ADMIN_OVERVIEW) {
            return [];
        }

        $statistics = $contextData['statistics'] ?? [];

        return [
            'lastRegisteredUsers' => $statistics['lastRegisteredUsers'] ?? [],
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
