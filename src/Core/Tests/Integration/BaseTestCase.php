<?php

namespace App\Core\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
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
    }

    protected function resetDatabase(): void
    {
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE server');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE user');
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
