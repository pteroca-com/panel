<?php

namespace App\Core\Enum;

enum SettingEnum: string
{
    case SITE_URL = 'site_url';

    case SITE_TITLE = 'site_title';

    case LOGO = 'site_logo';

    case LOCALE = 'site_locale';

    case SITE_FAVICON = 'site_favicon';

    case EMAIL_SMTP_SERVER = 'smtp_server';

    case EMAIL_SMTP_PORT = 'smtp_port';

    case EMAIL_SMTP_USERNAME = 'smtp_username';

    case EMAIL_SMTP_PASSWORD = 'smtp_password';

    case EMAIL_SMTP_FROM = 'smtp_from';

    case PTERODACTYL_PANEL_URL = 'pterodactyl_url';

    case PTERODACTYL_API_KEY = 'pterodactyl_api_key';

    case STRIPE_SECRET_KEY = 'stripe_secret_key';

    case STRIPE_PAYMENT_METHODS = 'stripe_payment_methods';

    case INTERNAL_CURRENCY_NAME = 'internal_currency_name';

    case CURRENCY_NAME = 'currency_name';

    case DEFAULT_THEME_PRIMARY_COLOR = 'theme_default_primary_color';

    case DEFAULT_THEME_DARK_PRIMARY_COLOR = 'theme_default_dark_primary_color';

    case SHOW_PHPMYADMIN_URL = 'show_phpmyadmin_url';

    case PHPMYADMIN_URL = 'phpmyadmin_url';

    case CUSTOMER_MOTD_ENABLED = 'customer_motd_enabled';

    case CUSTOMER_MOTD_MESSAGE = 'customer_motd_message';

    case CUSTOMER_MOTD_TITLE = 'customer_motd_title';

    case REQUIRE_EMAIL_VERIFICATION = 'require_email_verification';

    case GOOGLE_CAPTCHA_VERIFICATION = 'google_captcha_verification';

    case GOOGLE_CAPTCHA_SITE_KEY = 'google_captcha_site_key';

    case GOOGLE_CAPTCHA_SECRET_KEY = 'google_captcha_secret_key';
}