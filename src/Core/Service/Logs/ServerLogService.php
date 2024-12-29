<?php

namespace App\Core\Service\Logs;

use App\Core\DTO\PaginationDTO;
use App\Core\Entity\Server;
use App\Core\Entity\ServerLog;
use App\Core\Entity\User;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Repository\ServerLogRepository;

class ServerLogService
{
    public function __construct(
        private readonly ServerLogRepository $serverLogRepository,
    ) {}

    public function logServerAction(User $user, Server $server, ServerLogActionEnum $action, array $details = []): void
    {
        $serverLog = (new ServerLog())
            ->setActionId(strtolower($action->name))
            ->setUser($user)
            ->setServer($server)
            ->setDetails(json_encode($details))
        ;

        $this->serverLogRepository->save($serverLog);
    }

    public function getServerLogsWithPagination(Server $server, int $currentPage = 1): PaginationDTO
    {
        $serverLogs = $this->serverLogRepository
            ->findBy(['server' => $server], ['createdAt' => 'DESC'], 10, ($currentPage - 1) * 10);
        $totalItems = $this->serverLogRepository->count(['server' => $server]);

        return new PaginationDTO(
            $currentPage,
            ceil($totalItems / 10),
            $totalItems,
            $serverLogs,
        );
    }
}
