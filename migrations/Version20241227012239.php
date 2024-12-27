<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241227012239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `allow_change_egg` column to `product` table after `eggs_configuration` column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD allow_change_egg TINYINT(1) NOT NULL DEFAULT 0 AFTER eggs_configuration');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP allow_change_egg');
    }
}
