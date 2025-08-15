<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250815034400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configuration fee settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO setting (name, value, type, context, hierarchy) VALUES ('configuration_fee_enabled', '0', 'boolean', 'payment_settings', 15)");
        $this->addSql("INSERT INTO setting (name, value, type, context, hierarchy) VALUES ('configuration_fee_amount', '0.00', 'decimal', 'payment_settings', 16)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting WHERE name = 'configuration_fee_enabled'");
        $this->addSql("DELETE FROM setting WHERE name = 'configuration_fee_amount'");
    }
}
