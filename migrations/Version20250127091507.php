<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250127091507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add records to the `setting` table for the custom theme support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `setting` (`name`, `value`, `type`, `context`) VALUES ('current_theme', 'default', 'text', 'theme_settings')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `setting` WHERE `name` = 'current_theme'");
    }
}
