<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241127122406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a new column `pterodactyl_user_api_key` in the `user` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD pterodactyl_user_api_key VARCHAR(255) DEFAULT NULL AFTER pterodactyl_user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP pterodactyl_user_api_key');
    }
}
