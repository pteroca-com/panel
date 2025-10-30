<?php

namespace App\Core\Enum;

/**
 * Widget rendering contexts.
 *
 * Defines where widgets can be displayed in the application.
 * Each widget declares which contexts it supports via getSupportedContexts().
 *
 * Layout contexts:
 * - DASHBOARD: User dashboard page (/panel)
 * - ADMIN_OVERVIEW: Admin overview page (/admin/overview)
 * - SERVER_DETAIL: Server detail page (future)
 * - USER_PROFILE: User profile page (future)
 */
enum WidgetContext: string
{
    case DASHBOARD = 'dashboard';
    case ADMIN_OVERVIEW = 'admin_overview';
    case SERVER_DETAIL = 'server_detail';
    case USER_PROFILE = 'user_profile';
}
