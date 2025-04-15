<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250413171914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add voucher and voucher_usage tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE voucher (
                id INT AUTO_INCREMENT NOT NULL,
                code VARCHAR(255) NOT NULL,
                value NUMERIC(10, 2) NOT NULL,
                type VARCHAR(255) NOT NULL,
                new_accounts_only TINYINT(1) NOT NULL,
                minimum_topup_amount NUMERIC(10, 2) DEFAULT NULL,
                minimum_order_amount NUMERIC(10, 2) DEFAULT NULL,
                expiration_date DATETIME DEFAULT NULL,
                max_global_uses INT DEFAULT NULL,
                used_count INT NOT NULL,
                one_use_per_user TINYINT(1) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                deleted_at DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_VOUCHER_CODE (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE voucher_usage (
                id INT AUTO_INCREMENT NOT NULL,
                voucher_id INT NOT NULL,
                user_id INT NOT NULL,
                used_at DATETIME NOT NULL,
                INDEX IDX_VOUCHER_ID (voucher_id),
                INDEX IDX_USER_ID (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_VOUCHER_ID FOREIGN KEY (voucher_id) REFERENCES voucher (id),
                CONSTRAINT FK_USER_ID FOREIGN KEY (user_id) REFERENCES user (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE voucher_usage');
        $this->addSql('DROP TABLE voucher');
    }
}
