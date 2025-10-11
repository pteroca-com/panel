<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251011101804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name column to server table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server ADD name VARCHAR(255) DEFAULT NULL AFTER pterodactyl_server_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server DROP name');
    }
}
