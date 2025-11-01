<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251031000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plugin table for PteroCA Plugin System';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE plugin (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(50) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            version VARCHAR(20) NOT NULL,
            author VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            license VARCHAR(50) NOT NULL,
            state VARCHAR(20) NOT NULL,
            path VARCHAR(500) NOT NULL,
            manifest JSON NOT NULL,
            pteroca_min_version VARCHAR(20) NOT NULL,
            pteroca_max_version VARCHAR(20) DEFAULT NULL,
            enabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            disabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            fault_reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_E740924C5E237E06 (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE plugin');
    }
}
