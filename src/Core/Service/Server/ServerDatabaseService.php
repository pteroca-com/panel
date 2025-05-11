<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerDatabaseService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
    ) {}

    public function getAllDatabases(
        Server $server,
        UserInterface $user,
    ): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->server_databases
            ->http
            ->get("servers/{$server->getPterodactylServerIdentifier()}/databases", [
                'include' => 'password'
            ])
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

        $pterodactylClientApi = $this->pterodactylClientService->getApi($user);

        $pterodactylClientApi->server_databases->create($server->getPterodactylServerIdentifier(), [
            'database' => $databaseName,
            'remote' => $connectionsFrom,
        ]);

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
        $this->pterodactylClientService
            ->getApi($user)
            ->server_databases
            ->delete($server->getPterodactylServerIdentifier(), $databaseId);

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
        $rotatedPassword = $this->pterodactylClientService
            ->getApi($user)
            ->server_databases
            ->http
            ->post("servers/{$server->getPterodactylServerIdentifier()}/databases/$databaseId/rotate-password")
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
