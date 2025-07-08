<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250708142015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add show_pterodactyl_logs_in_server_activity setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type, context) VALUES (\'show_pterodactyl_logs_in_server_activity\', \'1\', \'boolean\', \'pterodactyl_settings\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'show_pterodactyl_logs_in_server_activity\'');
    }
}
