<?php

namespace App\Core\Tests\Integration;

use App\Core\Enum\SettingEnum;
use App\Core\Handler\Installer\DefaultSystemSettingConfiguratorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseTestCase extends WebTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $this->resetDatabase();
        $this->loadFixtures();
    }

    protected function resetDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function loadFixtures(): void
    {
        $defaultSettings = DefaultSystemSettingConfiguratorHandler::DEFAULT_SETTINGS;
        foreach ($defaultSettings as $name => $value) {
            switch ($name) {
                case SettingEnum::STRIPE_SECRET_KEY->value:
                    $value['value'] = 'sk_test_123456';
                    break;
                case SettingEnum::PTERODACTYL_API_KEY->value:
                    $value['value'] = 'api_key_test_123456';
                    break;
            }
            $this->entityManager->getConnection()->insert('setting', [
                'name' => $name,
                'value' => $value['value'],
                'type' => $value['type'],
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null; // Unikamy wycieków pamięci
        }

        parent::tearDown();
    }
}
