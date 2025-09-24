<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250913222644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to user table for soft delete functionality';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP deleted_at');
    }
}
