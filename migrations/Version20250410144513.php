<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250410144513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at and deleted_at fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD deleted_at DATETIME DEFAULT NULL AFTER updated_at');
        $this->addSql('ALTER TABLE product_price ADD deleted_at DATETIME DEFAULT NULL AFTER price');

        $this->addSql('ALTER TABLE server ADD deleted_at DATETIME DEFAULT NULL AFTER created_at');
        $this->addSql('ALTER TABLE server_product_price ADD deleted_at DATETIME DEFAULT NULL AFTER is_selected');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP deleted_at');
        $this->addSql('ALTER TABLE product_price DROP deleted_at');

        $this->addSql('ALTER TABLE server DROP deleted_at');
        $this->addSql('ALTER TABLE server_product_price DROP deleted_at');
    }
}
