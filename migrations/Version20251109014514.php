<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109014514 extends AbstractMigration
{
    private const SETTINGS_TO_RENAME = [
        'theme_default_primary_color' => 'theme_default_light_mode_color',
        'theme_default_dark_background_color' => 'theme_default_dark_mode_color',
    ];

    private const SETTINGS_TO_DELETE = [
        'theme_default_background_color',
        'theme_default_background_content_color',
        'theme_default_dark_primary_color',
        'theme_default_dark_background_content_color',
    ];

    public function getDescription(): string
    {
        return 'Simplify theme customization to 2 nullable settings: theme_default_light_mode_color and theme_default_dark_mode_color';
    }

    public function up(Schema $schema): void
    {
        // Rename settings, set to null, and mark as nullable (so default template generates)
        foreach (self::SETTINGS_TO_RENAME as $oldName => $newName) {
            $this->addSql(
                'UPDATE setting SET name = :newName, nullable = 1, value = NULL WHERE name = :oldName',
                ['oldName' => $oldName, 'newName' => $newName]
            );
        }

        // Delete 4 unused settings
        foreach (self::SETTINGS_TO_DELETE as $settingName) {
            $this->addSql(
                'DELETE FROM setting WHERE name = :name',
                ['name' => $settingName]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Revert renamed settings to original names
        foreach (self::SETTINGS_TO_RENAME as $oldName => $newName) {
            $this->addSql(
                'UPDATE setting SET name = :oldName, nullable = 0 WHERE name = :newName',
                ['oldName' => $oldName, 'newName' => $newName]
            );
        }

        // Re-insert the 4 deleted settings (with default values)
        $settingsToRestore = [
            [
                'name' => 'theme_default_background_color',
                'value' => null,
                'type' => 'color',
                'context' => 'theme_settings',
                'hierarchy' => 100,
                'nullable' => 0,
            ],
            [
                'name' => 'theme_default_background_content_color',
                'value' => null,
                'type' => 'color',
                'context' => 'theme_settings',
                'hierarchy' => 100,
                'nullable' => 0,
            ],
            [
                'name' => 'theme_default_dark_primary_color',
                'value' => null,
                'type' => 'color',
                'context' => 'theme_settings',
                'hierarchy' => 100,
                'nullable' => 0,
            ],
            [
                'name' => 'theme_default_dark_background_content_color',
                'value' => null,
                'type' => 'color',
                'context' => 'theme_settings',
                'hierarchy' => 100,
                'nullable' => 0,
            ],
        ];

        foreach ($settingsToRestore as $setting) {
            $this->addSql(
                'INSERT INTO setting (name, value, type, context, hierarchy, nullable) VALUES (:name, :value, :type, :context, :hierarchy, :nullable)',
                $setting
            );
        }
    }
}
