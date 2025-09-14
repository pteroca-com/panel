<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250902091048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create setting_option table for select type settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting_option (
            id INT AUTO_INCREMENT NOT NULL,
            setting_name VARCHAR(255) NOT NULL,
            option_key VARCHAR(255) NOT NULL,
            option_value VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_setting_name (setting_name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting_option');
    }
}
