<?php

namespace App\Core\Tests\Unit\Service\Statistics;

use App\Core\Repository\PaymentRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Statistics\AdminStatisticsService;
use PHPUnit\Framework\TestCase;

class AdminStatisticsServiceTest extends TestCase
{
    public function testGetAdminStatistics(): void
    {
        $serverRepository = $this->createMock(ServerRepository::class);
        $serverRepository->expects($this->once())
            ->method('getActiveServersCount')
            ->willReturn(5);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getUsersRegisteredAfterCount')
            ->willReturn(10);
        $userRepository->expects($this->once())
            ->method('getLastRegisteredUsers')
            ->willReturn([]);

        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository->expects($this->once())
            ->method('getPaymentsCreatedAfterCount')
            ->willReturn(15);
        $paymentRepository->expects($this->once())
            ->method('getLastPayments')
            ->willReturn([]);

        $adminStatisticsService = new AdminStatisticsService($serverRepository, $userRepository, $paymentRepository);
        $statistics = $adminStatisticsService->getAdminStatistics();

        $this->assertEquals([
            'activeServers' => 5,
            'usersRegisteredLastMonth' => 10,
            'paymentsCreatedLastMonth' => 15,
            'lastRegisteredUsers' => [],
            'lastPayments' => [],
        ], $statistics);
    }
}
