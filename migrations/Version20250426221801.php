<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250426221801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add balance and used voucher columns to the payment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD balance_amount DECIMAL(10,2) DEFAULT NULL AFTER currency');
        $this->addSql('ALTER TABLE payment ADD used_voucher INT DEFAULT NULL AFTER balance_amount');

        $this->addSql('UPDATE payment SET amount = amount / 100');
        $this->addSql('UPDATE payment SET balance_amount = amount');

        $this->addSql('ALTER TABLE payment MODIFY balance_amount DECIMAL(10,2) NOT NULL');

        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6A2B8E0D4F34D596 FOREIGN KEY (used_voucher) REFERENCES voucher (id)');
        $this->addSql('CREATE INDEX IDX_6A2B8E0D4F34D596 ON payment (used_voucher)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6A2B8E0D4F34D596');
        $this->addSql('DROP INDEX IDX_6A2B8E0D4F34D596 ON payment');
        $this->addSql('UPDATE payment SET amount = amount * 100');
        $this->addSql('ALTER TABLE payment DROP used_voucher');
        $this->addSql('ALTER TABLE payment DROP balance_amount');
    }
}
