<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

class ServerDatabaseService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly ServerLogService $serverLogService,
    ) {}

    public function getAllDatabases(
        Server $server,
        UserInterface $user,
    ): array
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->getDatabases($server->getPterodactylServerIdentifier(), ['include' => 'password'])
            ->toArray();
    }

    public function createDatabase(
        Server $server,
        UserInterface $user,
        string $databaseName,
        string $connectionsFrom,
    ): void
    {
        if (empty($connectionsFrom)) {
            $connectionsFrom = '%';
        }

        $pterodactylClientApi = $this->pterodactylApplicationService
            ->getClientApi($user);

        $pterodactylClientApi->databases()
            ->createDatabase(
                $server->getPterodactylServerIdentifier(),
                $databaseName,
                $connectionsFrom
            );

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::CREATE_DATABASE,
            [
                'database' => $databaseName,
                'remote' => $connectionsFrom,
            ]
        );
    }

    public function deleteDatabase(
        Server $server,
        UserInterface $user,
        int $databaseId,
    ): void
    {
        $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->deleteDatabase($server->getPterodactylServerIdentifier(), $databaseId);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_DATABASE,
            [
                'database_id' => $databaseId,
            ]
        );
    }

    public function rotatePassword(
        Server $server,
        UserInterface $user,
        string $databaseId,
    ): array
    {
        $rotatedPassword = $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->rotatePassword($server->getPterodactylServerIdentifier(), $databaseId)
            ->toArray();

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::ROTATE_DATABASE_PASSWORD,
            [
                'database_id' => $databaseId,
            ]
        );

        return $rotatedPassword;
    }
}
