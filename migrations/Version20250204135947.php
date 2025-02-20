<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250204135947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new DELETE_SUSPENDED_SERVERS_DAYS_AFTER and DELETE_SUSPENDED_SERVERS_ENABLED setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'delete_suspended_servers_enabled\', \'1\', \'boolean\', \'general_settings\')');
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'delete_suspended_servers_days_after\', \'30\', \'number\', \'general_settings\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'delete_suspended_servers_enabled\'');
        $this->addSql('DELETE FROM setting WHERE name = \'delete_suspended_servers_days_after\'');
    }
}
