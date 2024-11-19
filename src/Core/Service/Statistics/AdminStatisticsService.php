<?php

namespace App\Core\Service\Statistics;

use App\Core\Repository\PaymentRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;

readonly class AdminStatisticsService
{
    public function __construct(
        private ServerRepository $serverRepository,
        private UserRepository $userRepository,
        private PaymentRepository $paymentRepository,
    )
    {
    }

    public function getAdminStatistics(): array
    {
        return [
            'activeServers' => $this->serverRepository->getActiveServersCount(),
            'usersRegisteredLastMonth' => $this->userRepository->getUsersRegisteredAfterCount(new \DateTime('-1 month')),
            'paymentsCreatedLastMonth' => $this->paymentRepository->getPaymentsCreatedAfterCount(new \DateTime('-1 month')),
            'lastRegisteredUsers' => $this->userRepository->getLastRegisteredUsers(5),
            'lastPayments' => $this->paymentRepository->getLastPayments(5),
        ];
    }
}
