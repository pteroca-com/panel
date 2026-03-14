<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar upload settings (max size and allowed extensions) as select type with predefined options';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'avatar_max_size', '2M', 'select', 'security_settings', 50, 0
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'avatar_max_size')
        ");

        $this->addSql("
            INSERT INTO setting (name, value, type, context, hierarchy, nullable)
            SELECT 'avatar_allowed_extensions', 'image/jpeg, image/png', 'select', 'security_settings', 51, 0
            WHERE NOT EXISTS (SELECT 1 FROM setting WHERE name = 'avatar_allowed_extensions')
        ");

        $this->addSql("
            INSERT INTO setting_option (setting_name, option_key, option_value, sort_order, created_at, updated_at) VALUES
            ('avatar_max_size', '512 KB', '512K', 0, NOW(), NOW()),
            ('avatar_max_size', '1 MB', '1M', 1, NOW(), NOW()),
            ('avatar_max_size', '2 MB', '2M', 2, NOW(), NOW()),
            ('avatar_max_size', '5 MB', '5M', 3, NOW(), NOW()),
            ('avatar_max_size', '10 MB', '10M', 4, NOW(), NOW())
        ");

        $this->addSql("
            INSERT INTO setting_option (setting_name, option_key, option_value, sort_order, created_at, updated_at) VALUES
            ('avatar_allowed_extensions', 'JPEG, PNG', 'image/jpeg, image/png', 0, NOW(), NOW()),
            ('avatar_allowed_extensions', 'JPEG, PNG, WebP', 'image/jpeg, image/png, image/webp', 1, NOW(), NOW()),
            ('avatar_allowed_extensions', 'JPEG, PNG, GIF', 'image/jpeg, image/png, image/gif', 2, NOW(), NOW()),
            ('avatar_allowed_extensions', 'JPEG, PNG, GIF, WebP', 'image/jpeg, image/png, image/gif, image/webp', 3, NOW(), NOW()),
            ('avatar_allowed_extensions', 'JPEG, PNG, GIF, WebP, SVG', 'image/jpeg, image/png, image/gif, image/webp, image/svg+xml', 4, NOW(), NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting_option WHERE setting_name = 'avatar_max_size'");
        $this->addSql("DELETE FROM setting_option WHERE setting_name = 'avatar_allowed_extensions'");
        $this->addSql("DELETE FROM setting WHERE name = 'avatar_max_size'");
        $this->addSql("DELETE FROM setting WHERE name = 'avatar_allowed_extensions'");
    }
}
