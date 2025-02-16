<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250126190537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `auto_renewal` column to `server` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server ADD auto_renewal TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server DROP auto_renewal');
    }
}
