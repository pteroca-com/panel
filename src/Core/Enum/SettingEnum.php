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
    case PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL = 'pterodactyl_use_as_client_panel';
    case PTERODACTYL_API_KEY = 'pterodactyl_api_key';
    case PTERODACTYL_SSO_ENABLED = 'pterodactyl_sso_enabled';
    case PTERODACTYL_SSO_SECRET = 'pterodactyl_sso_secret';
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
    case TERMS_OF_SERVICE = 'terms_of_service';
    case DELETE_SUSPENDED_SERVERS_ENABLED = 'delete_suspended_servers_enabled';
    case DELETE_SUSPENDED_SERVERS_DAYS_AFTER = 'delete_suspended_servers_days_after';
    case CURRENT_THEME = 'current_theme';
}
