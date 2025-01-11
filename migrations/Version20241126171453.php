<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126171453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new column `eggs_configuration` to product table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD eggs_configuration LONGTEXT DEFAULT NULL AFTER eggs');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP eggs_configuration');
    }
}
