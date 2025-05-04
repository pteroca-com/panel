<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250406124345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add server_product_price table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE server_product_price (
            id INT AUTO_INCREMENT NOT NULL, 
            server_product_id INT NOT NULL,
            type VARCHAR(255) NOT NULL,
            value INT NOT NULL,
            unit VARCHAR(255) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            is_selected TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY(id)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE server_product_price ADD CONSTRAINT FK_3D3A3D33A4612265A FOREIGN KEY (server_product_id) REFERENCES server_product (id)');

        $serverProducts = $this->connection->fetchAllAssociative('SELECT * FROM server_product');
        foreach ($serverProducts as $serverProduct) {
            $productPrices = $this->connection->fetchAllAssociative(
                'SELECT * FROM product_price WHERE product_id = :product_id',
                ['product_id' => $serverProduct['original_product_id']]
            );

            foreach ($productPrices as $key => $productPrice) {
                $this->addSql('INSERT INTO server_product_price (server_product_id, type, value, unit, price, is_selected) VALUES (:server_product_id, :type, :value, :unit, :price, :is_selected)', [
                    'server_product_id' => $serverProduct['id'],
                    'type' => $productPrice['type'],
                    'value' => $productPrice['value'],
                    'unit' => $productPrice['unit'],
                    'price' => $productPrice['price'],
                    'is_selected' => ($key === 0) ? 1 : 0,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE server_product_price');
    }
}
