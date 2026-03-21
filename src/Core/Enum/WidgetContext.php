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
 * - SERVER_DETAIL: Server detail page (/server?id=...)
 * - SERVER_LIST: Server list page (/servers)
 * - USER_PROFILE: User profile page (future)
 * - CART_CONFIGURE: Cart configure page (/cart/configure)
 * - CART_RENEW: Cart renew page (/cart/renew)
 * - CART_TOPUP: Cart top-up balance page (/cart/topup)
 * - STORE_HOME: Store home page (/panel/store)
 * - STORE_PRODUCT: Store product detail page (/panel/store/product)
 * - LANDING_HOMEPAGE: Landing page homepage (/)
 * - LANDING_STORE: Landing page store (/store)
 */
enum WidgetContext: string
{
    case DASHBOARD = 'dashboard';
    case ADMIN_OVERVIEW = 'admin_overview';
    case SERVER_DETAIL = 'server_detail';
    case SERVER_LIST = 'server_list';
    case USER_PROFILE = 'user_profile';
    case CART_CONFIGURE = 'cart_configure';
    case CART_RENEW = 'cart_renew';
    case CART_TOPUP = 'cart_topup';
    case STORE_HOME = 'store_home';
    case STORE_PRODUCT = 'store_product';
    case LANDING_HOMEPAGE = 'landing_homepage';
    case LANDING_STORE = 'landing_store';
}
