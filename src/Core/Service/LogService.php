<?php

namespace App\Core\Service;

use App\Core\Entity\Log;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Repository\LogRepository;
use App\Core\Service\System\IpAddressProviderService;

readonly class LogService
{
    public function __construct(
        private LogRepository $logRepository,
        private IpAddressProviderService $ipAddressProviderService
    ) {}

    public function logAction(User $user, LogActionEnum $action, array $details = []): void
    {
        $newLog = (new Log())
            ->setActionId($action->value)
            ->setUser($user)
            ->setDetails(json_encode($details))
            ->setIpAddress($this->ipAddressProviderService->getIpAddress() ?? 'Unknown');
        $this->logRepository->save($newLog);
    }

    public function getLogsByUser(User $user, ?int $limit): array
    {
        return $this->logRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit);
    }
}