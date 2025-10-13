<?php

namespace App\Core\Enum;

enum ViewNameEnum: string
{
    case LOGIN = 'login';
    case REGISTRATION = 'registration';
    case DASHBOARD = 'dashboard';
    case SERVERS_LIST = 'servers_list';
    case BALANCE_RECHARGE = 'balance_recharge';
    case CART_TOPUP = 'cart_topup';
    case CART_CONFIGURE = 'cart_configure';
    case CART_RENEW = 'cart_renew';
    case STORE_INDEX = 'store_index';
    case STORE_CATEGORY = 'store_category';
    case STORE_PRODUCT = 'store_product';
}
