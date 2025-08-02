<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add recharge minimum and maximum amount settings
 */
final class Version20250802175001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add recharge minimum and maximum amount settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'recharge_min_amount\', \'0.50\', \'number\', \'payment_settings\')');
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'recharge_max_amount\', \'1000.00\', \'number\', \'payment_settings\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'recharge_min_amount\'');
        $this->addSql('DELETE FROM setting WHERE name = \'recharge_max_amount\'');
    }
}
