<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731202312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_payment (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(255) NOT NULL, status VARCHAR(255) DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, balance_amount NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, used_voucher INT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_35259A0794458029 (used_voucher), INDEX IDX_35259A07A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_payment ADD CONSTRAINT FK_35259A0794458029 FOREIGN KEY (used_voucher) REFERENCES voucher (id)');
        $this->addSql('ALTER TABLE user_payment ADD CONSTRAINT FK_35259A07A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX IDX_1F1B251A7D3656A4 ON log');
        $this->addSql('ALTER TABLE payment RENAME INDEX idx_6a2b8e0d4f34d596 TO IDX_6D28840D94458029');
        $this->addSql('ALTER TABLE product CHANGE eggs_configuration eggs_configuration VARCHAR(255) DEFAULT NULL, CHANGE allow_change_egg allow_change_egg TINYINT(1) NOT NULL, CHANGE schedules schedules INT NOT NULL');
        $this->addSql('ALTER TABLE product_price ADD has_free_trial TINYINT(1) NOT NULL, ADD free_trial_value INT DEFAULT NULL, ADD free_trial_unit VARCHAR(255) DEFAULT NULL, CHANGE product_id product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_price RENAME INDEX fk_3d3a3d3a4584665a TO IDX_6B9459854584665A');
        $this->addSql('DROP INDEX IDX_5C7E2D3A7D3656A4 ON server_log');
        $this->addSql('ALTER TABLE server_log CHANGE details details LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE server_log RENAME INDEX user_id TO IDX_B1F6629FA76ED395');
        $this->addSql('ALTER TABLE server_log RENAME INDEX server_id TO IDX_B1F6629F1844E6B7');
        $this->addSql('ALTER TABLE server_product DROP INDEX FK_3D3A3D3A4584665A63441329, ADD UNIQUE INDEX UNIQ_A1341F111844E6B7 (server_id)');
        $this->addSql('ALTER TABLE server_product DROP INDEX FK_3D3A3D3A4584666312335B70, ADD UNIQUE INDEX UNIQ_A1341F11283311CE (original_product_id)');
        $this->addSql('ALTER TABLE server_product CHANGE nodes nodes JSON DEFAULT NULL, CHANGE eggs eggs JSON DEFAULT NULL, CHANGE eggs_configuration eggs_configuration VARCHAR(255) DEFAULT NULL, CHANGE allow_change_egg allow_change_egg TINYINT(1) NOT NULL, CHANGE schedules schedules INT NOT NULL');
        $this->addSql('ALTER TABLE server_product_price ADD has_free_trial TINYINT(1) NOT NULL, ADD free_trial_value INT DEFAULT NULL, ADD free_trial_unit VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE server_product_price RENAME INDEX fk_3d3a3d33a4612265a TO IDX_CD394789974BF6FD');
        $this->addSql('DROP INDEX UNIQ_server_subuser_server_user ON server_subuser');
        $this->addSql('ALTER TABLE server_subuser RENAME INDEX idx_server_subuser_server_id TO IDX_2658BF7E1844E6B7');
        $this->addSql('ALTER TABLE server_subuser RENAME INDEX idx_server_subuser_user_id TO IDX_2658BF7EA76ED395');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE voucher RENAME INDEX uniq_voucher_code TO UNIQ_1392A5D877153098');
        $this->addSql('ALTER TABLE voucher_usage RENAME INDEX idx_voucher_id TO IDX_1550084E28AA1B6F');
        $this->addSql('ALTER TABLE voucher_usage RENAME INDEX idx_user_id TO IDX_1550084EA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_payment DROP FOREIGN KEY FK_35259A0794458029');
        $this->addSql('ALTER TABLE user_payment DROP FOREIGN KEY FK_35259A07A76ED395');
        $this->addSql('DROP TABLE user_payment');
        $this->addSql('CREATE INDEX IDX_1F1B251A7D3656A4 ON log (created_at)');
        $this->addSql('ALTER TABLE payment RENAME INDEX idx_6d28840d94458029 TO IDX_6A2B8E0D4F34D596');
        $this->addSql('ALTER TABLE product CHANGE schedules schedules INT DEFAULT 10 NOT NULL, CHANGE eggs_configuration eggs_configuration LONGTEXT DEFAULT NULL, CHANGE allow_change_egg allow_change_egg TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE product_price DROP has_free_trial, DROP free_trial_value, DROP free_trial_unit, CHANGE product_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_price RENAME INDEX idx_6b9459854584665a TO FK_3D3A3D3A4584665A');
        $this->addSql('ALTER TABLE server_log CHANGE details details TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_5C7E2D3A7D3656A4 ON server_log (created_at)');
        $this->addSql('ALTER TABLE server_log RENAME INDEX idx_b1f6629fa76ed395 TO user_id');
        $this->addSql('ALTER TABLE server_log RENAME INDEX idx_b1f6629f1844e6b7 TO server_id');
        $this->addSql('ALTER TABLE server_product DROP INDEX UNIQ_A1341F111844E6B7, ADD INDEX FK_3D3A3D3A4584665A63441329 (server_id)');
        $this->addSql('ALTER TABLE server_product DROP INDEX UNIQ_A1341F11283311CE, ADD INDEX FK_3D3A3D3A4584666312335B70 (original_product_id)');
        $this->addSql('ALTER TABLE server_product CHANGE schedules schedules INT DEFAULT 10 NOT NULL, CHANGE nodes nodes LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE eggs eggs LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE eggs_configuration eggs_configuration LONGTEXT DEFAULT NULL, CHANGE allow_change_egg allow_change_egg TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE server_product_price DROP has_free_trial, DROP free_trial_value, DROP free_trial_unit');
        $this->addSql('ALTER TABLE server_product_price RENAME INDEX idx_cd394789974bf6fd TO FK_3D3A3D33A4612265A');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_server_subuser_server_user ON server_subuser (server_id, user_id)');
        $this->addSql('ALTER TABLE server_subuser RENAME INDEX idx_2658bf7e1844e6b7 TO IDX_server_subuser_server_id');
        $this->addSql('ALTER TABLE server_subuser RENAME INDEX idx_2658bf7ea76ed395 TO IDX_server_subuser_user_id');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE voucher RENAME INDEX uniq_1392a5d877153098 TO UNIQ_VOUCHER_CODE');
        $this->addSql('ALTER TABLE voucher_usage RENAME INDEX idx_1550084e28aa1b6f TO IDX_VOUCHER_ID');
        $this->addSql('ALTER TABLE voucher_usage RENAME INDEX idx_1550084ea76ed395 TO IDX_USER_ID');
    }
}
