<?php

namespace App\Core\Tests\Unit\Service;

use App\Core\Entity\Log;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Repository\LogRepository;
use App\Core\Service\Logs\LogService;
use App\Core\Service\System\IpAddressProviderService;
use PHPUnit\Framework\TestCase;

class LogServiceTest extends TestCase
{
    private LogRepository $logRepository;
    private IpAddressProviderService $ipAddressProviderService;
    private LogService $logService;

    protected function setUp(): void
    {
        $this->logRepository = $this->createMock(LogRepository::class);
        $this->ipAddressProviderService = $this->createMock(IpAddressProviderService::class);

        $this->logService = new LogService(
            $this->logRepository,
            $this->ipAddressProviderService
        );
    }

    public function testLogAction(): void
    {
        $user = $this->createMock(User::class);
        $action = LogActionEnum::LOGIN;
        $details = ['key' => 'value'];

        $this->ipAddressProviderService
            ->method('getIpAddress')
            ->willReturn('127.0.0.1');

        $this->logRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Log $log) use ($user, $action, $details) {
                return $log->getUser() === $user &&
                    $log->getActionId() === strtolower($action->name) &&
                    $log->getDetails() === json_encode($details) &&
                    $log->getIpAddress() === '127.0.0.1';
            }));

        $this->logService->logAction($user, $action, $details);
    }

    public function testLogActionWithUnknownIp(): void
    {
        $user = $this->createMock(User::class);
        $action = LogActionEnum::LOGIN;

        $this->ipAddressProviderService
            ->method('getIpAddress')
            ->willReturn(null);

        $this->logRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Log $log) use ($user, $action) {
                return $log->getUser() === $user &&
                    $log->getActionId() === strtolower($action->name) &&
                    $log->getIpAddress() === 'Unknown';
            }));

        $this->logService->logAction($user, $action);
    }

    public function testGetLogsByUserWithoutLimit(): void
    {
        $user = $this->createMock(User::class);
        $logs = [new Log(), new Log()];

        $this->logRepository
            ->method('findBy')
            ->with(['user' => $user], ['createdAt' => 'DESC'], null)
            ->willReturn($logs);

        $result = $this->logService->getLogsByUser($user, null);

        $this->assertCount(2, $result);
        $this->assertSame($logs, $result);
    }

    public function testGetLogsByUserWithLimit(): void
    {
        $user = $this->createMock(User::class);
        $logs = [new Log()];

        $this->logRepository
            ->method('findBy')
            ->with(['user' => $user], ['createdAt' => 'DESC'], 1)
            ->willReturn($logs);

        $result = $this->logService->getLogsByUser($user, 1);

        $this->assertCount(1, $result);
        $this->assertSame($logs, $result);
    }
}
