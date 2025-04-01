<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250331183059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new product_price table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_price (
            id INT AUTO_INCREMENT NOT NULL, 
            product_id INT NOT NULL,
            type VARCHAR(255) NOT NULL,
            value INT NOT NULL,
            unit VARCHAR(255) NOT NULL,
            price NUMERIC(10, 2) NOT NULL, 
            PRIMARY KEY(id)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE product_price ADD CONSTRAINT FK_3D3A3D3A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_price');
    }
}
