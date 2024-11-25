<?php

namespace App\Core\Tests\Unit\Service\System;

use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\System\SystemInformationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SystemInformationServiceTest extends TestCase
{
    public function testGetSystemInformation(): void
    {
        $pterodactylService = $this->createMock(PterodactylService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $systemInformationService = new SystemInformationService($entityManager, $pterodactylService);
        $systemInformation = $systemInformationService->getSystemInformation();

        $this->assertArrayHasKey('php', $systemInformation);
        $this->assertArrayHasKey('database', $systemInformation);
        $this->assertArrayHasKey('os', $systemInformation);
        $this->assertArrayHasKey('webserver', $systemInformation);
        $this->assertArrayHasKey('pterodactyl', $systemInformation);
    }
}
