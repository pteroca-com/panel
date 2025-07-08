<?php

namespace App\Core\Service\Logs;

use App\Core\Contract\UserInterface;
use App\Core\DTO\PaginationDTO;
use App\Core\DTO\ServerLogDTO;
use App\Core\Entity\Server;
use App\Core\Entity\ServerLog;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Enum\ServerLogSourceTypeEnum;
use App\Core\Repository\ServerLogRepository;

class ServerLogService
{
    public function __construct(
        private readonly ServerLogRepository $serverLogRepository,
    ) {}

    public function logServerAction(UserInterface $user, Server $server, ServerLogActionEnum $action, array $details = []): void
    {
        $serverLog = (new ServerLog())
            ->setActionId(strtolower($action->name))
            ->setUser($user)
            ->setServer($server)
            ->setDetails(json_encode($details))
        ;

        $this->serverLogRepository->save($serverLog);
    }

    public function getServerLogsWithPagination(
        Server $server,
        array $pterodactylActivityLogs = [],
        int $currentPage = 1
    ): PaginationDTO {
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $pteroDtos = [];

        if (!empty($pterodactylActivityLogs)) {
            $pteroDtos = array_map(fn ($log) => new ServerLogDTO(
                null,
                ServerLogSourceTypeEnum::PTERODACTYL,
                strtolower($log['attributes']['event']),
                null,
                $server,
                new \DateTime($log['attributes']['timestamp']),
                json_encode($log['attributes'])
            ), $pterodactylActivityLogs);
        }


        $dbLogs = $this->serverLogRepository->findBy(['server' => $server]);
        $dbDtos = array_map(fn (ServerLog $log) => new ServerLogDTO(
            $log->getId(),
            ServerLogSourceTypeEnum::PTEROCA,
            $log->getActionId(),
            $log->getUser(),
            $log->getServer(),
            $log->getCreatedAt(),
            $log->getDetails(),
        ), $dbLogs);

        $allLogs = array_merge($pteroDtos, $dbDtos);
        usort($allLogs, fn (ServerLogDTO $a, ServerLogDTO $b) => $b->createdAt <=> $a->createdAt);
        

        $paginatedLogs = array_slice($allLogs, $offset, $perPage);

        $totalItems = count($allLogs);

        return new PaginationDTO(
            $currentPage,
            ceil($totalItems / $perPage),
            $totalItems,
            $paginatedLogs,
        );
    }

    public function deleteServerActionLogs(Server $server): void
    {
        $this->serverLogRepository->deleteServerLogs($server->getId());
    }
}
