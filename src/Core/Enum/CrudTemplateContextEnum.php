<?php

namespace App\Core\Enum;

enum CrudTemplateContextEnum: string
{
    case CATEGORY = 'category';
    case EMAIL_LOG = 'email_log';
    case LOG = 'log';
    case PAYMENT = 'payment';
    case PRODUCT = 'product';
    case SERVER = 'server';
    case SERVER_LOG = 'server_log';
    case SERVER_PRODUCT = 'server_product';
    case USER = 'user';
    case SETTING = 'setting';
    case VOUCHER = 'voucher';
    case VOUCHER_USAGE = 'voucher_usage';
    case USER_ACCOUNT = 'user_account';
    case USER_PAYMENT = 'user_payment';
}
