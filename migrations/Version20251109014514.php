<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109014514 extends AbstractMigration
{
    private const NULLABLE_SETTINGS = [
        'theme_default_primary_color',
        'theme_default_background_color',
        'theme_default_dark_primary_color',
        'theme_default_dark_background_color',
    ];

    public function getDescription(): string
    {
        return 'Mark 4 theme color settings as nullable and set empty values to NULL';
    }

    public function up(Schema $schema): void
    {
        // Mark settings as nullable
        foreach (self::NULLABLE_SETTINGS as $settingName) {
            $this->addSql(
                'UPDATE setting SET nullable = 1 WHERE name = :name',
                ['name' => $settingName]
            );
        }

        // Set values to NULL for nullable settings
        foreach (self::NULLABLE_SETTINGS as $settingName) {
            $this->addSql(
                'UPDATE setting SET value = NULL WHERE name = :name',
                ['name' => $settingName]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Revert nullable flag
        foreach (self::NULLABLE_SETTINGS as $settingName) {
            $this->addSql(
                'UPDATE setting SET nullable = 0 WHERE name = :name',
                ['name' => $settingName]
            );
        }
    }
}
