<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240909130631 extends AbstractMigration
{
    private const DEFAULT_SETTINGS = [
        'theme_default_primary_color' => [
            'type' => 'color',
            'value' => '#4e73df',
        ],
        'theme_default_dark_primary_color' => [
            'type' => 'color',
            'value' => '#2e59d9',
        ],
        'show_phpmyadmin_url' => [
            'type' => 'boolean',
            'value' => '0',
        ],
        'phpmyadmin_url' => [
            'type' => 'url',
            'value' => '#',
        ],
        'customer_motd_enabled' => [
            'type' => 'boolean',
            'value' => '0',
        ],
        'customer_motd_title' => [
            'type' => 'text',
            'value' => 'Message of the day',
        ],
        'customer_motd_message' => [
            'type' => 'twig',
            'value' => 'Welcome to our store!',
        ],
        'require_email_verification' => [
            'type' => 'boolean',
            'value' => '0',
        ],
        'google_captcha_verification' => [
            'type' => 'boolean',
            'value' => '0',
        ],
        'google_captcha_site_key' => [
            'type' => 'secret',
            'value' => '',
        ],
        'google_captcha_secret_key' => [
            'type' => 'secret',
            'value' => '',
        ],
        'site_logo' => [
            'type' => 'image',
            'value' => '',
        ],
        'site_favicon' => [
            'type' => 'image',
            'value' => '',
        ],
        'site_url' => [
            'type' => 'url',
            'value' => 'http://localhost',
        ],
        'site_title' => [
            'type' => 'text',
            'value' => 'Pteroca',
        ],
        'site_locale' => [
            'type' => 'locale',
            'value' => 'en',
        ],
        'smtp_server' => [
            'type' => 'text',
            'value' => 'smtp.mailtrap.io',
        ],
        'smtp_port' => [
            'type' => 'text',
            'value' => '2525',
        ],
        'smtp_username' => [
            'type' => 'text',
            'value' => '',
        ],
        'smtp_password' => [
            'type' => 'text',
            'value' => '',
        ],
        'smtp_from' => [
            'type' => 'text',
            'value' => '',
        ],
        'pterodactyl_url' => [
            'type' => 'url',
            'value' => 'http://localhost',
        ],
        'pterodactyl_api_key' => [
            'type' => 'secret',
            'value' => '',
        ],
        'stripe_secret_key' => [
            'type' => 'secret',
            'value' => '',
        ],
        'stripe_payment_methods' => [
            'type' => 'text',
            'value' => 'card',
        ],
        'currency_name' => [
            'type' => 'text',
            'value' => 'USD',
        ],
        'internal_currency_name' => [
            'type' => 'text',
            'value' => 'USD',
        ],
    ];

    public function getDescription(): string
    {
        return 'Default settings';
    }

    public function up(Schema $schema): void
    {
        foreach (self::DEFAULT_SETTINGS as $name => $setting) {
            if ($this->connection->fetchOne('SELECT COUNT(*) FROM setting WHERE name = ?', [$name]) === 0) {
                $this->addSql('INSERT INTO setting (name, type, value) VALUES (?, ?, ?)', [
                    $name,
                    $setting['type'],
                    $setting['value'],
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No need to remove settings
    }
}
