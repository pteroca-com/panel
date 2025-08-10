<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250810181741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove sidebar_style appearance setting';
    }

    public function up(Schema $schema): void
    {
        // Remove any existing sidebar_style setting
        $this->addSql("DELETE FROM setting WHERE name = 'sidebar_style'");
    }

    public function down(Schema $schema): void
    {
        // Recreate the setting if the migration is rolled back (kept benign)
        $this->addSql("INSERT INTO setting (name, value, type, context, hierarchy) VALUES ('sidebar_style', 'current', 'string', 'theme_settings', 35)");
    }
}
