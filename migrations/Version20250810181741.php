<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250810181741 extends AbstractMigration
{
    private const NEW_SETTINGS = [
        [
            'name' => 'sidebar_style',
            'value' => 'current',
            'type' => 'string',
            'context' => 'theme_settings',
            'hierarchy' => 35,
        ],
    ];

    public function getDescription(): string
    {
        return 'Add appearance setting to choose sidebar style (current vs. future younglin)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::NEW_SETTINGS as $setting) {
            $this->addSql('INSERT INTO setting (name, value, type, context, hierarchy) VALUES (:name, :value, :type, :context, :hierarchy)', $setting);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::NEW_SETTINGS as $setting) {
            $this->addSql('DELETE FROM setting WHERE name = :name', $setting);
        }
    }
}
