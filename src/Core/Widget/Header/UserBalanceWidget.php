<?php

namespace App\Core\Widget\Header;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * User balance widget displayed in the navbar.
 *
 * Shows the current user's balance with a link to recharge page.
 * This widget is global and appears across all contexts.
 */
class UserBalanceWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'user_balance';
    }

    public function getDisplayName(): string
    {
        return 'User Balance';
    }

    public function getSupportedContexts(): array
    {
        // Support all contexts - this is a global navbar widget
        return [
            WidgetContext::DASHBOARD,
            WidgetContext::ADMIN_OVERVIEW,
            WidgetContext::SERVER_DETAIL,
            WidgetContext::USER_PROFILE,
        ];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::NAVBAR;
    }

    public function getPriority(): int
    {
        return 100; // High priority - display first in navbar
    }

    public function getTemplate(): string
    {
        return 'components/header/user_balance.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        // Template uses app.user directly, no additional data needed
        return [];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        // Always visible when user is authenticated
        return isset($contextData['user']);
    }

    public function getColumnSize(): int
    {
        return 12; // Not applicable for navbar widgets
    }
}
