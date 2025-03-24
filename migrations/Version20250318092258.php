<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Core\Enum\SettingEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250318092258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Disable web wizard if already configured';
    }

    public function up(Schema $schema): void
    {
        if ($this->isPterodactylApiKeyExist()) {
            $this->addSql('UPDATE setting SET value = 1 WHERE name = "is_configured"');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->isPterodactylApiKeyExist()) {
            $this->addSql('UPDATE setting SET value = 0 WHERE name = "is_configured"');
        }
    }

    private function isPterodactylApiKeyExist(): bool
    {
        $setting = $this->connection->fetchAssociative(
            'SELECT * FROM setting WHERE name = :name',
            ['name' => SettingEnum::PTERODACTYL_API_KEY->value]
        );

        return !empty($setting['value']);
    }
}
