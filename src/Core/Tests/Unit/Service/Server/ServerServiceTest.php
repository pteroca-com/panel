<?php

namespace App\Core\Tests\Unit\Service\Server;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerService;
use PHPUnit\Framework\TestCase;
use Timdesm\PterodactylPhpApi\Managers\ServerManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Egg;
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
                    'egg' => new Egg(['name' => 'Test Egg']),
                ],
            ],
        ];

        $pterodactylServer = new PterodactylServer($pterodactylServerData, $pterodactylApi);
        $pterodactylServer->set('name', 'Test Server');
        $pterodactylServer->set('description', 'Test Description');

        $pterodactylApi->servers = $this->createMock(ServerManager::class);
        $pterodactylApi->servers
            ->method('get')
            ->with(123, ['include' => ['allocations', 'egg']])
            ->willReturn($pterodactylServer);

        $result = $this->serverService->getServerDetails($server);

        $this->assertNotNull($result);
        $this->assertEquals('192.168.1.1:25565', $result->ip);
        $this->assertEquals(['memory' => 2048], $result->limits);
        $this->assertEquals(['databases' => 3], $result->featureLimits);
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
            ->with(123, ['include' => ['allocations', 'egg']])
            ->willReturn(new PterodactylServer([], $pterodactylApi));

        $result = $this->serverService->getServerDetails($server);

        $this->assertNull($result);
    }

    public function testGetServer(): void
    {
        $server = new Server();
        $server->setPterodactylServerIdentifier(123);

        $this->serverRepository
            ->method('findOneBy')
            ->with(['pterodactylServerIdentifier' => $server->getPterodactylServerIdentifier()])
            ->willReturn($server);

        $result = $this->serverService->getServer($server->getPterodactylServerIdentifier());

        $this->assertSame($server, $result);
    }

    public function testGetServerNotFound(): void
    {
        $this->serverRepository
            ->method('findOneBy')
            ->with(['pterodactylServerIdentifier' => 123])
            ->willReturn(null);

        $result = $this->serverService->getServer(123);

        $this->assertNull($result);
    }
}
