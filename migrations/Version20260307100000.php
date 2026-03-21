<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zip_hash column to plugin table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plugin ADD zip_hash VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plugin DROP COLUMN zip_hash');
    }
}
