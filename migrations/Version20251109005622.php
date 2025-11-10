<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109005622 extends AbstractMigration
{
    private const REMOVED_SETTINGS = [
        ['name' => 'theme_default_secondary_color'],
        ['name' => 'theme_default_link_color'],
        ['name' => 'theme_default_link_hover_color'],
        ['name' => 'theme_default_dark_secondary_color'],
        ['name' => 'theme_default_dark_link_color'],
        ['name' => 'theme_default_dark_link_hover_color'],
    ];

    public function getDescription(): string
    {
        return 'Remove unused theme color settings (simplified to 4 variables with automatic color derivation)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::REMOVED_SETTINGS as $setting) {
            $this->addSql('DELETE FROM setting WHERE name = :name', $setting);
        }
    }

    public function down(Schema $schema): void
    {
        // Restore settings with empty values if rolled back
        foreach (self::REMOVED_SETTINGS as $setting) {
            $this->addSql(
                'INSERT INTO setting (name, value, type, context, hierarchy) VALUES (:name, NULL, \'string\', \'theme_settings\', 100)',
                $setting
            );
        }
    }
}
