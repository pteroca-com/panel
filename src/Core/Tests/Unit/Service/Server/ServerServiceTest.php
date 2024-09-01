<?php

namespace App\Core\Tests\Unit\Service\Server;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerService;
use PHPUnit\Framework\TestCase;
use Timdesm\PterodactylPhpApi\Managers\ServerManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class ServerServiceTest extends TestCase
{
    private PterodactylService $pterodactylService;
    private ServerRepository $serverRepository;
    private ServerService $serverService;

    protected function setUp(): void
    {
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->serverRepository = $this->createMock(ServerRepository::class);
        $this->serverService = new ServerService($this->pterodactylService, $this->serverRepository);
    }

    public function testGetServerDetailsWithValidServer(): void
    {
        $server = $this->createMock(Server::class);
        $server->method('getPterodactylServerId')->willReturn(123);

        $pterodactylApi = $this->createMock(PterodactylApi::class);
        $this->pterodactylService->method('getApi')->willReturn($pterodactylApi);

        $pterodactylServerData = [
            'attributes' => [
                'limits' => ['memory' => 2048],
                'feature_limits' => ['databases' => 3],
                'relationships' => [
                    'allocations' => [
                        [
                            'ip' => '192.168.1.1',
                            'port' => 25565,
                        ]
                    ],
                ],
            ],
        ];

        $pterodactylServer = new PterodactylServer($pterodactylServerData, $pterodactylApi);

        $pterodactylApi->servers = $this->createMock(ServerManager::class);
        $pterodactylApi->servers
            ->method('get')
            ->with(123, ['include' => ['allocations']])
            ->willReturn($pterodactylServer);

        $result = $this->serverService->getServerDetails($server);

        $this->assertNotNull($result);
        $this->assertEquals('192.168.1.1:25565', $result['ip']);
        $this->assertEquals(['memory' => 2048], $result['limits']);
        $this->assertEquals(['databases' => 3], $result['feature-limits']);
    }

    public function testGetServerDetailsWithInvalidServer(): void
    {
        $server = $this->createMock(Server::class);
        $server->method('getPterodactylServerId')->willReturn(123);

        $pterodactylApi = $this->createMock(PterodactylApi::class);
        $this->pterodactylService->method('getApi')->willReturn($pterodactylApi);

        $pterodactylApi->servers = $this->createMock(ServerManager::class);
        $pterodactylApi->servers
            ->method('get')
            ->with(123, ['include' => ['allocations']])
            ->willReturn(new PterodactylServer([], $pterodactylApi));

        $result = $this->serverService->getServerDetails($server);

        $this->assertNull($result);
    }

    public function testGetServer(): void
    {
        $server = new Server();
        $this->serverRepository
            ->method('find')
            ->with(123)
            ->willReturn($server);

        $result = $this->serverService->getServer(123);

        $this->assertSame($server, $result);
    }

    public function testGetServerNotFound(): void
    {
        $this->serverRepository
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $result = $this->serverService->getServer(123);

        $this->assertNull($result);
    }
}
