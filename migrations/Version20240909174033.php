<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240909174033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert terms of service setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO setting (name, type, value) VALUES (?, ?, ?)', [
            'terms_of_service',
            'twig',
            '<h1>Terms of service</h1> <p>You can set content of this page in settings.</p>',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM setting WHERE name = ?', ['terms_of_service']);
    }
}
