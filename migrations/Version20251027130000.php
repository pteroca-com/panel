<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251027130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gateway field to payment table for payment gateway tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD gateway VARCHAR(50) NOT NULL DEFAULT \'stripe\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP gateway');
    }
}
