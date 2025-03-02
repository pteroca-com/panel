<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerDatabaseService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
    ) {}

    public function getAllDatabases(
        Server $server,
        User $user,
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
        User $user,
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
    }

    public function deleteDatabase(
        Server $server,
        User $user,
        int $databaseId,
    ): void
    {
        $this->pterodactylClientService
            ->getApi($user)
            ->server_databases
            ->delete($server->getPterodactylServerIdentifier(), $databaseId);
    }

    public function rotatePassword(
        Server $server,
        User $user,
        string $databaseId,
    ): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->server_databases
            ->http
            ->post("servers/{$server->getPterodactylServerIdentifier()}/databases/$databaseId/rotate-password")
            ->toArray();
    }
}