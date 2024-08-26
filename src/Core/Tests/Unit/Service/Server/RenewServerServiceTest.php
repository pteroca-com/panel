<?php

namespace App\Core\Tests\Unit\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Managers\ServerManager;
use Timdesm\PterodactylPhpApi\Managers\UserManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class RenewServerServiceTest extends TestCase
{
    private PterodactylService $pterodactylService;
    private ServerRepository $serverRepository;
    private SettingService $settingService;
    private ServerService $serverService;
    private TranslatorInterface $translator;
    private MessageBusInterface $messageBus;
    private UserRepository $userRepository;
    private RenewServerService $renewServerService;

    protected function setUp(): void
    {
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->serverRepository = $this->createMock(ServerRepository::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->serverService = $this->createMock(ServerService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->renewServerService = new RenewServerService(
            $this->pterodactylService,
            $this->serverRepository,
            $this->settingService,
            $this->serverService,
            $this->translator,
            $this->messageBus,
            $this->userRepository
        );
    }

    public function testRenewServer(): void
    {
        $product = $this->createMock(Product::class);
        $server = $this->createMock(Server::class);
        $user = $this->createMock(User::class);

        $user->method('getPterodactylUserId')->willReturn(1);
        $user->method('getEmail')->willReturn('test@test.com');
        $server->method('getExpiresAt')->willReturnCallback(function () {
            static $first = true;
            if ($first) {
                $first = false;
                return new \DateTime('-1 day');
            }
            return new \DateTime('+1 month');
        });
        $server->method('getIsSuspended')->willReturnCallback(function () {
            static $first = true;
            if ($first) {
                $first = false;
                return true;
            }
            return false;
        });
        $server->method('getPterodactylServerId')->willReturn(1);
        $server->method('getProduct')->willReturn($product);
        $server->expects($this->once())->method('setExpiresAt')->with($this->isInstanceOf(\DateTime::class));
        $product->method('getPrice')->willReturn(100.0);

        $serverManagerMock = $this->createMock(ServerManager::class);
        $serverManagerMock
            ->expects($this->once())
            ->method('unsuspend')
            ->with(1);

        $userManagerMock = $this->createMock(UserManager::class);
        $userManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn((object) ['username' => 'test']);

        $pterodactylApi = $this->createMock(PterodactylApi::class);
        $pterodactylApi->servers = $serverManagerMock;
        $pterodactylApi->users = $userManagerMock;

        $this->serverRepository
            ->expects($this->once())
            ->method('save')
            ->with($server);

        $this->pterodactylService
            ->expects($this->exactly(2))
            ->method('getApi')
            ->willReturn($pterodactylApi);

        $this->serverService
            ->expects($this->once())
            ->method('getServerDetails')
            ->willReturn(['ip' => '127.0.0.1']);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SendEmailMessage::class))
            ->willReturn(new Envelope(new SendEmailMessage(
                'test@test.com',
                'pteroca.email.renew.subject',
                'email/renew_product.html.twig',
                []
            )));

        $this->renewServerService->renewServer($server, $user);

        $this->assertEquals(
            (new \DateTime())->modify('+1 month')->format('Y-m-d H:i'),
            $server->getExpiresAt()->format('Y-m-d H:i')
        );
        $this->assertFalse($server->getIsSuspended());
    }

    public function testRenewServerWhenNotSuspended(): void
    {
        $product = $this->createMock(Product::class);
        $server = $this->createMock(Server::class);
        $user = $this->createMock(User::class);

        $user->method('getPterodactylUserId')->willReturn(1);
        $user->method('getEmail')->willReturn('test@test.com');
        $server->method('getExpiresAt')->willReturnCallback(function () {
            static $first = true;
            if ($first) {
                $first = false;
                return new \DateTime();
            }
            return new \DateTime('+1 month');
        });
        $server->method('getIsSuspended')->willReturn(false);
        $server->method('getPterodactylServerId')->willReturn(1);
        $server->method('getProduct')->willReturn($product);
        $server->expects($this->once())->method('setExpiresAt')->with($this->isInstanceOf(\DateTime::class));
        $product->method('getPrice')->willReturn(100.0);

        $serverManagerMock = $this->createMock(ServerManager::class);
        $serverManagerMock
            ->expects($this->never())
            ->method('unsuspend');

        $userManagerMock = $this->createMock(UserManager::class);
        $userManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn((object) ['username' => 'test']);

        $pterodactylApi = $this->createMock(PterodactylApi::class);
        $pterodactylApi->servers = $serverManagerMock;
        $pterodactylApi->users = $userManagerMock;

        $this->serverRepository
            ->expects($this->once())
            ->method('save')
            ->with($server);

        $this->pterodactylService
            ->expects($this->once())
            ->method('getApi')
            ->willReturn($pterodactylApi);

        $this->serverService
            ->expects($this->once())
            ->method('getServerDetails')
            ->willReturn(['ip' => '127.0.0.1']);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SendEmailMessage::class))
            ->willReturn(new Envelope(new SendEmailMessage(
                'test@test.com',
                'pteroca.email.renew.subject',
                'email/renew_product.html.twig',
                []
            )));

        $this->renewServerService->renewServer($server, $user);

        $this->assertEquals(
            (new \DateTime())->modify('+1 month')->format('Y-m-d H:i'),
            $server->getExpiresAt()->format('Y-m-d H:i')
        );
        $this->assertFalse($server->getIsSuspended());
    }

}
