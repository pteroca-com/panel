<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250309181442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to the server_log and log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_5C7E2D3A7D3656A4 ON server_log (created_at)');
        $this->addSql('CREATE INDEX IDX_1F1B251A7D3656A4 ON log (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_5C7E2D3A7D3656A4 ON server_log');
        $this->addSql('DROP INDEX IDX_1F1B251A7D3656A4 ON log');
    }
}
