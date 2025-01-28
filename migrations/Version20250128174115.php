<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250128174115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `banner_path` column to `product` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD banner_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP banner_path');
    }
}
