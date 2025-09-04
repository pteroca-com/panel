<?php

namespace App\Core\Enum;

enum ProductPriceTypeEnum: string
{
    case STATIC = 'static';
    case ON_DEMAND = 'on_demand';
    case SLOT = 'slot';
}
