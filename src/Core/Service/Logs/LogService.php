<?php

namespace App\Core\Service\Logs;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Log;
use App\Core\Enum\LogActionEnum;
use App\Core\Repository\LogRepository;
use App\Core\Service\System\IpAddressProviderService;

class LogService
{
    public function __construct(
        private readonly LogRepository $logRepository,
        private readonly IpAddressProviderService $ipAddressProviderService
    ) {}

    public function logAction(UserInterface $user, LogActionEnum $action, array $details = []): void
    {
        $newLog = (new Log())
            ->setActionId(strtolower($action->name))
            ->setUser($user)
            ->setDetails(json_encode($details))
            ->setIpAddress($this->ipAddressProviderService->getIpAddress() ?? 'Unknown');
        $this->logRepository->save($newLog);
    }

    public function getLogsByUser(UserInterface $user, ?int $limit): array
    {
        return $this->logRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit);
    }
}