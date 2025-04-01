<?php

namespace App\Core\Enum;

enum ProductPriceTypeEnum: string
{
    case FIXED_DAYS = 'fixed_days';
    case DYNAMIC_MINUTES = 'dynamic_minutes';
}
