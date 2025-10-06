<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250923201022 extends AbstractMigration
{
    private const NEW_SETTINGS = [
        [
            'name' => 'renewal_notification_enabled',
            'value' => '0',
            'type' => 'boolean',
            'context' => 'email_settings',
            'hierarchy' => 100,
        ],
        [
            'name' => 'renewal_notification_min_period_hours',
            'value' => '24',
            'type' => 'integer',
            'context' => 'email_settings',
            'hierarchy' => 100,
        ],
        [
            'name' => 'renewal_notification_on_demand_min_hours',
            'value' => '4',
            'type' => 'integer',
            'context' => 'email_settings',
            'hierarchy' => 100,
        ],
    ];

    public function getDescription(): string
    {
        return 'Add renewal notification settings';
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
