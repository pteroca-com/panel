<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250204125527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `avatar_path` column to `user` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD avatar_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP avatar_path');
    }
}
