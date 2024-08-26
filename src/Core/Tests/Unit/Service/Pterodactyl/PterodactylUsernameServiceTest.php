<?php

namespace App\Core\Tests\Unit\Service\Pterodactyl;

use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Pterodactyl\PterodactylUsernameService;
use PHPUnit\Framework\TestCase;
use Timdesm\PterodactylPhpApi\Managers\UserManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Collection;

class PterodactylUsernameServiceTest extends TestCase
{
    private PterodactylService $pterodactylService;
    private PterodactylUsernameService $usernameService;

    protected function setUp(): void
    {
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->usernameService = new PterodactylUsernameService($this->pterodactylService);
    }

    public function testGenerateUsernameWhenUsernameIsAvailable(): void
    {
        $apiMock = $this->createMock(PterodactylApi::class);
        $apiMock->users = $this->createMock(UserManager::class);
        $this->pterodactylService->method('getApi')->willReturn($apiMock);

        $apiMock->users
            ->method('all')
            ->with(['filter' => ['username' => 'testuser']])
            ->willReturn($this->createMock(Collection::class));

        $username = $this->usernameService->generateUsername('testuser@example.com');
        $this->assertEquals('testuser', $username);
    }

    public function testGenerateUsernameWhenEmailContainsPlus(): void
    {
        $apiMock = $this->createMock(PterodactylApi::class);
        $apiMock->users = $this->createMock(UserManager::class);
        $this->pterodactylService->method('getApi')->willReturn($apiMock);

        $apiMock->users
            ->method('all')
            ->with(['filter' => ['username' => 'testuser']])
            ->willReturn($this->createMock(Collection::class));

        $username = $this->usernameService->generateUsername('testuser+alias@example.com');
        $this->assertEquals('testuser', $username);
    }
}
