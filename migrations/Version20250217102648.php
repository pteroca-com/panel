<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250217102648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add priority column to setting table and new context for pterodactyl settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE setting SET context = \'pterodactyl_settings\' WHERE name IN (\'pterodactyl_url\', \'pterodactyl_api_key\', \'pterodactyl_use_as_client_panel\', \'pterodactyl_sso_enabled\', \'pterodactyl_sso_secret\')');

        $this->addSql('ALTER TABLE setting ADD hierarchy SMALLINT NOT NULL DEFAULT 100');
        $this->addSql('UPDATE setting SET hierarchy = 10 WHERE name IN (\'site_url\', \'site_title\', \'site_locale\')');
        $this->addSql('UPDATE setting SET hierarchy = 90 WHERE name IN (\'delete_suspended_servers_enabled\', \'delete_suspended_servers_days_after\')');


        $this->addSql('UPDATE setting SET hierarchy = 10 WHERE name IN (\'pterodactyl_url\', \'pterodactyl_api_key\')');

        $this->addSql('UPDATE setting SET hierarchy = 10 WHERE name IN (\'require_email_verification\')');
        $this->addSql('UPDATE setting SET hierarchy = 20 WHERE name IN (\'terms_of_service\')');

        $this->addSql('UPDATE setting SET hierarchy = 10 WHERE name IN (\'internal_currency_name\')');
        $this->addSql('UPDATE setting SET hierarchy = 20 WHERE name IN (\'currency_name\')');

        $this->addSql('UPDATE setting SET hierarchy = 10 WHERE name IN (\'site_logo\', \'site_favicon\')');
        $this->addSql('UPDATE setting SET hierarchy = 20 WHERE name IN (\'current_theme\')');
        $this->addSql('UPDATE setting SET hierarchy = 30 WHERE name IN (\'theme_default_primary_color\', \'theme_default_dark_primary_color\')');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE setting SET context = \'general_settings\' WHERE name IN (\'pterodactyl_url\', \'pterodactyl_api_key\', \'ptero_use_as_client_panel\', \'pterodactyl_sso_enabled\', \'pterodactyl_sso_secret\')');
        $this->addSql('ALTER TABLE setting DROP hierarchy');
    }
}
