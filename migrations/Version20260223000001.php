<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add pterodactyl_manage_in_panel_button_enabled setting
 */
final class Version20260223000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pterodactyl_manage_in_panel_button_enabled setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'pterodactyl_manage_in_panel_button_enabled', '0', 'boolean', 'pterodactyl_settings', 105, 0
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'pterodactyl_manage_in_panel_button_enabled')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting WHERE name = 'pterodactyl_manage_in_panel_button_enabled'");
    }
}
