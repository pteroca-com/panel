<?php

namespace App\Core\Enum;

enum VoucherTypeEnum: string
{
    case BALANCE_TOPUP = 'balance_topup';
    case DISCOUNT = 'discount';

    public static function getChoices(): array
    {
        return [
            'pteroca.crud.voucher.balance_topup' => self::BALANCE_TOPUP,
            'pteroca.crud.voucher.discount' => self::DISCOUNT,
        ];
    }
}
