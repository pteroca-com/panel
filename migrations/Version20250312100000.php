<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add validation_pattern and validation_normalizer to setting table.
 * When null/empty, accept-all regex is used so existing settings are unaffected.
 */
final class Version20250312100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add validation_pattern and validation_normalizer to setting table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE setting ADD validation_pattern VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE setting ADD validation_normalizer VARCHAR(100) DEFAULT NULL');
        $this->addSql(
            "UPDATE setting SET validation_pattern = '#^(?i)(light|dark|auto)$#', validation_normalizer = 'strtolower' WHERE name = 'theme_default_mode'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE setting SET validation_pattern = NULL, validation_normalizer = NULL WHERE name = 'theme_default_mode'");
        $this->addSql('ALTER TABLE setting DROP validation_pattern');
        $this->addSql('ALTER TABLE setting DROP validation_normalizer');
    }
}
