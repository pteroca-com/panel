<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250810100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add browser_language_sync setting with default OFF, set context and hierarchy';
    }

    public function up(Schema $schema): void
    {
        // Insert setting if not exists
        if ((int) $this->connection->fetchOne('SELECT COUNT(*) FROM setting WHERE name = ?', ['browser_language_sync']) === 0) {
            $this->addSql('INSERT INTO setting (name, type, value) VALUES (?, ?, ?)', [
                'browser_language_sync',
                'boolean',
                '0',
            ]);
        }

        // Ensure context and hierarchy
        $this->addSql("UPDATE setting SET context = 'general_settings' WHERE name = 'browser_language_sync'");
        $this->addSql("UPDATE setting SET hierarchy = 11 WHERE name = 'browser_language_sync'");
    }

    public function down(Schema $schema): void
    {
        // No need to remove the setting
    }
}
