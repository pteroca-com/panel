<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250913105310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create email_log table for tracking all sent emails with server relations and metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            server_id INT DEFAULT NULL,
            email_type VARCHAR(50) NOT NULL,
            email_address VARCHAR(255) NOT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            sent_at DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT \'sent\' NOT NULL,
            INDEX IDX_6FB48E7FA76ED395 (user_id),
            INDEX IDX_6FB48E7F1844E6B7 (server_id),
            INDEX IDX_6FB48E7F_email_type_server (email_type, server_id),
            INDEX IDX_6FB48E7F_sent_at (sent_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48E7FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48E7F1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB48E7FA76ED395');
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB48E7F1844E6B7');
        $this->addSql('DROP TABLE email_log');
    }
}
