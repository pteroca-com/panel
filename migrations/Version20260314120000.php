<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add context-specific logo settings (landing page and email)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'site_landing_logo', NULL, 'image', 'theme_settings', 11, 1
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'site_landing_logo')
        ");

        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'site_email_logo', NULL, 'image', 'email_settings', 10, 1
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'site_email_logo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting WHERE name = 'site_landing_logo'");
        $this->addSql("DELETE FROM setting WHERE name = 'site_email_logo'");
    }
}
