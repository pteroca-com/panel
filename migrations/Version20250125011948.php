<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250125011948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `context` column to `setting` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE setting ADD context VARCHAR(255) DEFAULT NULL');

        $this->addSql('UPDATE setting SET context = "general_settings" WHERE name IN ("site_url", "site_title", "site_locale", "pterodactyl_url", "pterodactyl_use_as_client_panel", "pterodactyl_api_key", "show_phpmyadmin_url", "phpmyadmin_url")');
        $this->addSql('UPDATE setting SET context = "email_settings" WHERE name IN ("smtp_server", "smtp_port", "smtp_username", "smtp_password", "smtp_from")');
        $this->addSql('UPDATE setting SET context = "payment_settings" WHERE name IN ("stripe_secret_key", "stripe_payment_methods", "internal_currency_name", "currency_name")');
        $this->addSql('UPDATE setting SET context = "theme_settings" WHERE name IN ("theme_default_primary_color", "theme_default_dark_primary_color", "customer_motd_enabled", "customer_motd_message", "customer_motd_title", "site_logo", "site_favicon")');
        $this->addSql('UPDATE setting SET context = "security_settings" WHERE name IN ("require_email_verification", "google_captcha_verification", "google_captcha_site_key", "google_captcha_secret_key", "terms_of_service")');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE setting DROP context');
    }
}
