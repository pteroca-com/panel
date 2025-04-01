<?php

namespace App\Core\Enum;

enum ProductPriceUnitEnum: string
{
    case DAYS = 'days';
    case HOURS = 'hours';
    case MINUTES = 'minutes';

    public static function getChoices(): array
    {
        return [
            'pteroca.crud.product.days' => self::DAYS,
            'pteroca.crud.product.hours' => self::HOURS,
            'pteroca.crud.product.minutes' => self::MINUTES,
        ];
    }
}
