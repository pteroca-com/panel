<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250318102118 extends AbstractMigration
{
    private const NEW_SETTINGS = [
        [
            'name' => 'theme_disable_dark_mode',
            'value' => '0',
            'type' => 'boolean',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_mode',
            'value' => 'light',
            'type' => 'string',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_background_color',
            'value' => '#ffffff',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_link_color',
            'value' => '#5c70d6',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_link_hover_color',
            'value' => '#99a6e6',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_background_color',
            'value' => '#14172e',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_link_color',
            'value' => '#5d6bc6',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_link_hover_color',
            'value' => '#7c8bfd',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_secondary_color',
            'value' => '#f8fafc',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_secondary_color',
            'value' => '#1c222c',
            'type' => 'color',
            'context' => 'theme_settings',
            'hierarchy' => 30,
        ],
    ];

    private const SETTINGS_TO_UPDATE = [
        [
            'name' => 'theme_default_dark_primary_color',
            'oldValue' => '#2e59d9',
            'newValue' => '#1f2347',
        ]
    ];
    public function getDescription(): string
    {
        return 'New appearance settings';
    }

    public function up(Schema $schema): void
    {
        foreach (self::NEW_SETTINGS as $setting) {
            $this->addSql('INSERT INTO setting (name, value, type, context, hierarchy) VALUES (:name, :value, :type, :context, :hierarchy)', $setting);
        }

        foreach (self::SETTINGS_TO_UPDATE as $setting) {
            $this->addSql('UPDATE setting SET value = :newValue WHERE name = :name', $setting);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::NEW_SETTINGS as $setting) {
            $this->addSql('DELETE FROM setting WHERE name = :name', $setting);
        }

        foreach (self::SETTINGS_TO_UPDATE as $setting) {
            $this->addSql('UPDATE setting SET value = :oldValue WHERE name = :name', $setting);
        }
    }
}
