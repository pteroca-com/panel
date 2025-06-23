<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250623174739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create server_subuser table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE server_subuser (
            id INT AUTO_INCREMENT NOT NULL, 
            server_id INT NOT NULL, 
            user_id INT NOT NULL, 
            permissions JSON NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME DEFAULT NULL, 
            INDEX IDX_server_subuser_server_id (server_id), 
            INDEX IDX_server_subuser_user_id (user_id), 
            UNIQUE INDEX UNIQ_server_subuser_server_user (server_id, user_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE server_subuser ADD CONSTRAINT FK_server_subuser_server_id FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE server_subuser ADD CONSTRAINT FK_server_subuser_user_id FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server_subuser DROP FOREIGN KEY FK_server_subuser_server_id');
        $this->addSql('ALTER TABLE server_subuser DROP FOREIGN KEY FK_server_subuser_user_id');
        $this->addSql('DROP TABLE server_subuser');
    }
}
