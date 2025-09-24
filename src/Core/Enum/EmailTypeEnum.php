<?php

namespace App\Core\Enum;

enum EmailTypeEnum: string
{
    case EMAIL_VERIFICATION = 'email_verification';
    case PAYMENT_SUCCESS = 'payment_success';
    case PURCHASED_PRODUCT = 'purchased_product';
    case REGISTRATION = 'registration';
    case RENEW_PRODUCT = 'renew_product';
    case RESET_PASSWORD = 'reset_password';
    case SERVER_SUSPENDED = 'server_suspended';
}
