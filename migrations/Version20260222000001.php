<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add configurable price format setting
 */
final class Version20260222000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add price_format setting with options for configurable price display';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'price_format', ',| ', 'select', 'general_settings', 19, 0
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'price_format')
        ");

        $this->addSql("
            INSERT INTO setting_option (setting_name, option_key, option_value, sort_order, created_at, updated_at) VALUES
            ('price_format', '1 234,56', ',| ', 0, NOW(), NOW()),
            ('price_format', '1,234.56', '.|,', 1, NOW(), NOW()),
            ('price_format', '1.234,56', ',|.', 2, NOW(), NOW()),
            ('price_format', '1''234.56', '.|''', 3, NOW(), NOW()),
            ('price_format', '1234,56', ',|', 4, NOW(), NOW()),
            ('price_format', '1234.56', '.|', 5, NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting_option WHERE setting_name = 'price_format'");
        $this->addSql("DELETE FROM setting WHERE name = 'price_format'");
    }
}
