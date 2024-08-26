<?php

namespace App\Core\Handler\Installer;

use App\Core\Enum\SettingEnum;
use App\Core\Enum\SettingTypeEnum;

class DefaultSystemSettingConfiguratorHandler
{
    public const DEFAULT_SETTINGS = [
        SettingEnum::DEFAULT_THEME_PRIMARY_COLOR->value => [
            'type' => SettingTypeEnum::COLOR->value,
            'value' => '#4e73df',
        ],
        SettingEnum::DEFAULT_THEME_DARK_PRIMARY_COLOR->value => [
            'type' => SettingTypeEnum::COLOR->value,
            'value' => '#2e59d9',
        ],
        SettingEnum::SHOW_PHPMYADMIN_URL->value => [
            'type' => SettingTypeEnum::BOOLEAN->value,
            'value' => '0',
        ],
        SettingEnum::PHPMYADMIN_URL->value => [
            'type' => SettingTypeEnum::URL->value,
            'value' => '#',
        ],
        SettingEnum::CUSTOMER_MOTD_ENABLED->value => [
            'type' => SettingTypeEnum::BOOLEAN->value,
            'value' => '0',
        ],
        SettingEnum::CUSTOMER_MOTD_TITLE->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'Message of the day',
        ],
        SettingEnum::CUSTOMER_MOTD_MESSAGE->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'Welcome to our store!',
        ],
        SettingEnum::REQUIRE_EMAIL_VERIFICATION->value => [
            'type' => SettingTypeEnum::BOOLEAN->value,
            'value' => '0',
        ],
        SettingEnum::GOOGLE_CAPTCHA_VERIFICATION->value => [
            'type' => SettingTypeEnum::BOOLEAN->value,
            'value' => '0',
        ],
        SettingEnum::GOOGLE_CAPTCHA_SITE_KEY->value => [
            'type' => SettingTypeEnum::SECRET->value,
            'value' => '',
        ],
        SettingEnum::GOOGLE_CAPTCHA_SECRET_KEY->value => [
            'type' => SettingTypeEnum::SECRET->value,
            'value' => '',
        ],
        SettingEnum::LOGO->value => [
            'type' => SettingTypeEnum::IMAGE->value,
            'value' => '',
        ],
        SettingEnum::SITE_FAVICON->value => [
            'type' => SettingTypeEnum::IMAGE->value,
            'value' => '',
        ],
        SettingEnum::SITE_URL->value => [
            'type' => SettingTypeEnum::URL->value,
            'value' => 'http://localhost',
        ],
        SettingEnum::SITE_TITLE->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'Pteroca',
        ],
        SettingEnum::LOCALE->value => [
            'type' => SettingTypeEnum::LOCALE->value,
            'value' => 'en',
        ],
        SettingEnum::EMAIL_SMTP_SERVER->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'smtp.mailtrap.io',
        ],
        SettingEnum::EMAIL_SMTP_PORT->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => '2525',
        ],
        SettingEnum::EMAIL_SMTP_USERNAME->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => '',
        ],
        SettingEnum::EMAIL_SMTP_PASSWORD->value => [
            'type' => SettingTypeEnum::SECRET->value,
            'value' => '',
        ],
        SettingEnum::EMAIL_SMTP_FROM->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => '',
        ],
        SettingEnum::PTERODACTYL_PANEL_URL->value => [
            'type' => SettingTypeEnum::URL->value,
            'value' => 'http://localhost',
        ],
        SettingEnum::PTERODACTYL_API_KEY->value => [
            'type' => SettingTypeEnum::SECRET->value,
            'value' => '',
        ],
        SettingEnum::STRIPE_SECRET_KEY->value => [
            'type' => SettingTypeEnum::SECRET->value,
            'value' => '',
        ],
        SettingEnum::STRIPE_PAYMENT_METHODS->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'card',
        ],
        SettingEnum::CURRENCY_NAME->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'USD',
        ],
        SettingEnum::INTERNAL_CURRENCY_NAME->value => [
            'type' => SettingTypeEnum::TEXT->value,
            'value' => 'USD',
        ],
    ];

    public function getDefaultSettingValue(SettingEnum $settingEnum): string
    {
        return self::DEFAULT_SETTINGS[$settingEnum->value]['value'];
    }

    public function getDefaultSettingType(SettingEnum $settingEnum): string
    {
        return self::DEFAULT_SETTINGS[$settingEnum->value]['type'];
    }
}