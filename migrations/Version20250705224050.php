<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250705224050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schedules column to product and server_product tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD schedules INT NOT NULL DEFAULT 10');
        
        $this->addSql('ALTER TABLE server_product ADD schedules INT NOT NULL DEFAULT 10');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server_product DROP schedules');
        
        $this->addSql('ALTER TABLE product DROP schedules');
    }
}
