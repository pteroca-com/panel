<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250311193917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_configured setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'is_configured\', \'0\', \'boolean\', \'hidden_settings\')');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'is_configured\'');
    }
}
