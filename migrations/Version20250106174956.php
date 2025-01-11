<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250106174956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type) VALUES (\'pterodactyl_use_as_client_panel\', \'0\', \'boolean\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'pterodactyl_use_as_client_panel\'');
    }
}
