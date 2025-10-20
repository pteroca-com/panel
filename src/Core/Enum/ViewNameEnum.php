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
    case TERMS_OF_SERVICE = 'terms_of_service';
    case PASSWORD_RESET_REQUEST = 'password_reset_request';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_VERIFICATION_NOTICE = 'email_verification_notice';
    case SSO_REDIRECT = 'sso_redirect';
}
