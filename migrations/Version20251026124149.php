<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251026124149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add purchase_token table for preventing race conditions and double-submit in cart purchases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE purchase_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            type VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_purchase_token_token (token),
            INDEX IDX_purchase_token_user_id (user_id),
            INDEX IDX_purchase_token_created_at (created_at),
            CONSTRAINT FK_purchase_token_user FOREIGN KEY (user_id)
                REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchase_token');
    }
}
