<?php

namespace App\Core\Tests\Unit\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\NodeSelectionService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Managers\Nest\NestEggManager;
use Timdesm\PterodactylPhpApi\Managers\ServerManager;
use Timdesm\PterodactylPhpApi\Managers\UserManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Egg as PterodactylEgg;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class CreateServerServiceTest extends TestCase
{
    private PterodactylService $pterodactylService;
    private ServerRepository $serverRepository;
    private NodeSelectionService $nodeSelectionService;
    private SettingService $settingService;
    private ServerService $serverService;
    private TranslatorInterface $translator;
    private MessageBusInterface $messageBus;
    private UserRepository $userRepository;
    private CreateServerService $createServerService;

    protected function setUp(): void
    {
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->serverRepository = $this->createMock(ServerRepository::class);
        $this->nodeSelectionService = $this->createMock(NodeSelectionService::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->serverService = $this->createMock(ServerService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->createServerService = new CreateServerService(
            $this->pterodactylService,
            $this->serverRepository,
            $this->nodeSelectionService,
            $this->settingService,
            $this->serverService,
            $this->translator,
            $this->messageBus,
            $this->userRepository
        );
    }

    public function testCreateServer(): void
    {
        $product = $this->createMock(Product::class);
        $user = $this->createMock(User::class);

        $user->method('getEmail')->willReturn('test@test.com');
        $user->method('getPterodactylUserId')->willReturn(1);
        $product->method('getNest')->willReturn(1);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getMemory')->willReturn(1024);
        $product->method('getDiskSpace')->willReturn(10000);
        $product->method('getPrice')->willReturn(100.0);

        $egg = $this->createMock(PterodactylEgg::class);
        $egg->id = 1;
        $egg->docker_image = 'docker_image';
        $egg->startup = 'startup_command';
        $egg->relationships = ['variables' => (object)['data' => []]];

        $pterodactylApiMock = $this->createMock(PterodactylApi::class);
        $pterodactylApiMock->nest_eggs = $this->createMock(NestEggManager::class);
        $pterodactylApiMock->servers = $this->createMock(ServerManager::class);
        $pterodactylApiMock->users = $this->createMock(UserManager::class);
        $this->pterodactylService->method('getApi')->willReturn($pterodactylApiMock);

        $pterodactylApiMock->nest_eggs
            ->method('get')
            ->with(1, 1, ['include' => 'variables'])
            ->willReturn($egg);

        $pterodactylApiMock->users
            ->method('get')
            ->with(1)
            ->willReturn((object)['username' => 'username']);

        $this->nodeSelectionService
            ->method('getBestAllocationId')
            ->with($product)
            ->willReturn(1);

        $createdPterodactylServer = new PterodactylServer([
            'attributes' => [
                'id' => 1,
                'identifier' => 'identifier',
            ],
        ], $pterodactylApiMock);

        $pterodactylApiMock->servers
            ->method('create')
            ->willReturn($createdPterodactylServer);

        $this->serverRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Server::class));

        $sendEmailMessage = new SendEmailMessage(
            'test@test.com',
            'pteroca.email.store.subject',
            'email/purchased_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
                'currency' => 'currency_name',
                'server' => [
                    'ip' => '127.0.0.1',
                    'expiresAt' => (new \DateTime('+1 month'))->format('Y-m-d H:i'),
                ],
                'panel' => [
                    'url' => 'http://panel.url',
                    'username' => 'username',
                ],
            ]
        );

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SendEmailMessage::class))
            ->willReturn(new Envelope($sendEmailMessage));

        $this->serverService
            ->expects($this->once())
            ->method('getServerDetails')
            ->willReturn([
                'ip' => '127.0.0.1'
            ]);

        $createdEntityServer = $this->createServerService->createServer($product, 1, $user);

        $this->assertInstanceOf(Server::class, $createdEntityServer);
        $this->assertEquals(1, $createdEntityServer->getPterodactylServerId());
        $this->assertEquals('identifier', $createdEntityServer->getPterodactylServerIdentifier());
    }

    public function testCreateServerThrowsExceptionWhenEggNotFound(): void
    {
        $product = $this->createMock(Product::class);
        $user = $this->createMock(User::class);

        $product->method('getNest')->willReturn(1);

        $pterodactylApiMock = $this->createMock(PterodactylApi::class);
        $pterodactylApiMock->nest_eggs = $this->createMock(NestEggManager::class);
        $this->pterodactylService->method('getApi')->willReturn($pterodactylApiMock);

        $pterodactylApiMock->nest_eggs
            ->method('get')
            ->with(1, 1, ['include' => 'variables'])
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Egg not found');

        $this->createServerService->createServer($product, 1, $user);
    }
}
