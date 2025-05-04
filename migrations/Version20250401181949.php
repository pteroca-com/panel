<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401181949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add server_product table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `server_product` (
          `id` INT AUTO_INCREMENT NOT NULL,
          `server_id` int(11) NOT NULL,
          `original_product_id` int(11) NULL,
          `name` varchar(255) NOT NULL,
          `disk_space` int(11) NOT NULL,
          `memory` int(11) NOT NULL,
          `io` int(11) NOT NULL,
          `cpu` int(11) NOT NULL,
          `db_count` int(11) NOT NULL,
          `swap` int(11) NOT NULL,
          `backups` int(11) NOT NULL,
          `ports` int(11) NOT NULL,
          `nodes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nodes`)),
          `nest` int(11) DEFAULT NULL,
          `eggs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eggs`)),
          `eggs_configuration` longtext DEFAULT NULL,
          `allow_change_egg` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `server_product` ADD CONSTRAINT `FK_3D3A3D3A4584665A63441329` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`)');
        $this->addSql('ALTER TABLE `server_product` ADD CONSTRAINT `FK_3D3A3D3A4584666312335B70` FOREIGN KEY (`original_product_id`) REFERENCES `product` (`id`)');

        $servers = $this->connection->fetchAllAssociative('SELECT * FROM server');
        foreach ($servers as $server) {
            $serverProduct = $this->connection->fetchAssociative('SELECT * FROM product WHERE id = :id', ['id' => $server['product_id']]);
            $this->addSql('INSERT INTO server_product (server_id, original_product_id, name, disk_space, memory, io, cpu, db_count, swap, backups, ports, nodes, nest, eggs, eggs_configuration, allow_change_egg) VALUES (:server_id, :original_product_id, :name, :disk_space, :memory, :io, :cpu, :db_count, :swap, :backups, :ports, :nodes, :nest, :eggs, :eggs_configuration, :allow_change_egg)', [
                'server_id' => $server['id'],
                'original_product_id' => $server['product_id'],
                'name' => $serverProduct['name'],
                'disk_space' => $serverProduct['disk_space'],
                'memory' => $serverProduct['memory'],
                'io' => $serverProduct['io'],
                'cpu' => $serverProduct['cpu'],
                'db_count' => $serverProduct['db_count'],
                'swap' => $serverProduct['swap'],
                'backups' => $serverProduct['backups'],
                'ports' => $serverProduct['ports'],
                'nodes' => $serverProduct['nodes'],
                'nest' => $serverProduct['nest'],
                'eggs' => $serverProduct['eggs'],
                'eggs_configuration' => $serverProduct['eggs_configuration'],
                'allow_change_egg' => $serverProduct['allow_change_egg'],
            ]);
        }

        $this->addSql('ALTER TABLE `server` DROP FOREIGN KEY `FK_5A6DD5F64584665A`');
        $this->addSql('ALTER TABLE `server` DROP product_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `server` ADD product_id INT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `server` ADD CONSTRAINT `FK_5A6DD5F64584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)');
        $this->addSql('UPDATE server s JOIN server_product sp ON s.id = sp.server_id SET s.product_id = sp.original_product_id');

        $this->addSql('DROP TABLE `server_product`');
    }
}
