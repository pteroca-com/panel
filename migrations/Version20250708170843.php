<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250708170843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add threads field to Product and ServerProduct tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD threads VARCHAR(255) DEFAULT NULL');
        
        $this->addSql('ALTER TABLE server_product ADD threads VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server_product DROP threads');
        
        $this->addSql('ALTER TABLE product DROP threads');
    }
}
