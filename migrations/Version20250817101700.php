<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250817101700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configuration_fee column to product (nullable decimal 10,2)';
    }

    public function up(Schema $schema): void
    {
        // Add nullable configuration_fee to product
        $this->addSql("ALTER TABLE product ADD configuration_fee NUMERIC(10, 2) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        // Drop configuration_fee column
        $this->addSql('ALTER TABLE product DROP configuration_fee');
    }
}
