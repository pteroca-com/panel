<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Core\Enum\SettingEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250913235844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add log cleanup settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO setting (name, value, type, context, hierarchy) VALUES (?, ?, ?, ?, ?)',
            [
                SettingEnum::LOG_CLEANUP_ENABLED->value,
                '0',
                'boolean',
                'system',
                100
            ]
        );

        $this->addSql(
            'INSERT INTO setting (name, value, type, context, hierarchy) VALUES (?, ?, ?, ?, ?)',
            [
                SettingEnum::LOG_CLEANUP_DAYS_AFTER->value,
                '90',
                'integer',
                'system',
                100
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM setting WHERE name = ?',
            [SettingEnum::LOG_CLEANUP_ENABLED->value]
        );

        $this->addSql(
            'DELETE FROM setting WHERE name = ?',
            [SettingEnum::LOG_CLEANUP_DAYS_AFTER->value]
        );
    }
}
