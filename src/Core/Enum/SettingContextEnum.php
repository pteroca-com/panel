<?php

namespace App\Core\Enum;

enum SettingContextEnum: string
{
    case GENERAL = 'general_settings';
    case THEME = 'theme_settings';
    case SECURITY = 'security_settings';
    case PAYMENT = 'payment_settings';
    case EMAIL = 'email_settings';
    case PTERODACTYL = 'pterodactyl_settings';
    case HIDDEN = 'hidden_settings';

    public static function getValues(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[strtoupper($case->name)] = $case->value;
        }

        return $values;
    }
}
