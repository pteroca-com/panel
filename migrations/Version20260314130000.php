<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add setup_fee column to product and server_product tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD setup_fee DECIMAL(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE server_product ADD setup_fee DECIMAL(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP COLUMN setup_fee');
        $this->addSql('ALTER TABLE server_product DROP COLUMN setup_fee');
    }
}
