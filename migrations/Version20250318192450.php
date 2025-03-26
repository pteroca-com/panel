<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250318192450 extends AbstractMigration
{
    private const NEW_HIERARCHY = [
        [
            'name' => 'theme_disable_dark_mode',
            'hierarchy' => 25,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_mode',
            'hierarchy' => 26,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'customer_motd_enabled',
            'hierarchy' => 50,
            'old_hierarchy' => 100,
        ],
        [
            'name' => 'customer_motd_title',
            'hierarchy' => 51,
            'old_hierarchy' => 100,
        ],
        [
            'name' => 'customer_motd_message',
            'hierarchy' => 52,
            'old_hierarchy' => 100,
        ],
        [
            'name' => 'theme_default_primary_color',
            'hierarchy' => 70,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_secondary_color',
            'hierarchy' => 71,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_background_color',
            'hierarchy' => 72,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_link_color',
            'hierarchy' => 73,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_link_hover_color',
            'hierarchy' => 74,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_primary_color',
            'hierarchy' => 80,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_secondary_color',
            'hierarchy' => 81,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_background_color',
            'hierarchy' => 82,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_link_color',
            'hierarchy' => 83,
            'old_hierarchy' => 30,
        ],
        [
            'name' => 'theme_default_dark_link_hover_color',
            'hierarchy' => 84,
            'old_hierarchy' => 30,
        ],
    ];

    public function getDescription(): string
    {
        return 'Update hierarchy of theme settings';
    }

    public function up(Schema $schema): void
    {
        foreach (self::NEW_HIERARCHY as $setting) {
            $this->addSql('UPDATE setting SET hierarchy = :hierarchy WHERE name = :name', $setting);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::NEW_HIERARCHY as $setting) {
            $this->addSql('UPDATE setting SET hierarchy = :old_hierarchy WHERE name = :name', $setting);
        }
    }
}
