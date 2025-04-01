<?php

namespace App\Core\Enum;

enum CrudTemplateContextEnum: string
{
    case CATEGORY = 'category';
    case LOG = 'log';
    case PAYMENT = 'payment';
    case PRODUCT = 'product';
    case SERVER = 'server';
    case SERVER_LOG = 'server_log';
    case SERVER_PRODUCT = 'server_product';
    case USER = 'user';
    case SETTING = 'setting';
}
