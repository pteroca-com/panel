<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211205333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new PTERODACTYL_SSO_SECRET and PTERODACTYL_SSO_ENABLED settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, value, type) VALUES (\'pterodactyl_sso_enabled\', \'0\', \'boolean\')');
        $this->addSql('INSERT INTO setting (name, value, type) VALUES (\'pterodactyl_sso_secret\', \'\', \'secret\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = \'pterodactyl_sso_enabled\'');
        $this->addSql('DELETE FROM setting WHERE name = \'pterodactyl_sso_secret\'');
    }
}
