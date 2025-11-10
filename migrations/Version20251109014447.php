<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109014447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable column to setting table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE setting ADD nullable TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE setting DROP nullable');
    }
}
