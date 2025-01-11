<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241229204622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the server_log table to store logs from the servers (id, server_id, user_id, action_id, details, created_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE server_log (
            id int AUTO_INCREMENT NOT NULL,
            server_id int NOT NULL,
            user_id int NOT NULL,
            action_id VARCHAR(255) NOT NULL,
            details TEXT DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY(id),
            FOREIGN KEY(server_id) REFERENCES server(id),
            FOREIGN KEY(user_id) REFERENCES user(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE server_log');
    }
}
