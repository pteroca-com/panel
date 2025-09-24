<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250902113200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change require_email_verification setting from boolean to select type and add setting options';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE setting SET type = 'select' WHERE name = 'require_email_verification'");
        
        $this->addSql("INSERT INTO setting_option (setting_name, option_key, option_value, sort_order, created_at, updated_at) VALUES 
            ('require_email_verification', 'disabled', 'Disabled', 0, NOW(), NOW()),
            ('require_email_verification', 'optional', 'Optional', 1, NOW(), NOW()),
            ('require_email_verification', 'required', 'Required', 2, NOW(), NOW())");
        

        $this->addSql("UPDATE setting SET value = 'disabled' WHERE name = 'require_email_verification' AND value = '0'");
        $this->addSql("UPDATE setting SET value = 'required' WHERE name = 'require_email_verification' AND value = '1'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE setting SET value = '0' WHERE name = 'require_email_verification' AND value = 'disabled'");
        $this->addSql("UPDATE setting SET value = '1' WHERE name = 'require_email_verification' AND value = 'required'");
        $this->addSql("UPDATE setting SET value = '0' WHERE name = 'require_email_verification' AND value = 'optional'");
        
        $this->addSql("UPDATE setting SET type = 'boolean' WHERE name = 'require_email_verification'");
        
        $this->addSql("DELETE FROM setting_option WHERE setting_name = 'require_email_verification'");
    }
}
