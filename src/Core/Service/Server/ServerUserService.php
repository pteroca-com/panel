<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerUserService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
    ) {}

    /**
     * Pobiera wszystkich subuserów serwera z Pterodactyla
     */
    public function getAllSubusers(Server $server, UserInterface $user): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->get("servers/{$server->getPterodactylServerIdentifier()}/users")
            ->toArray();
    }

    /**
     * Tworzy nowego subusera na serwerze
     */
    public function createSubuser(
        Server $server,
        UserInterface $user,
        string $email,
        array $permissions = []
    ): array
    {
        $pterodactylClientApi = $this->pterodactylClientService->getApi($user);

        $result = $pterodactylClientApi->http->post("servers/{$server->getPterodactylServerIdentifier()}/users", [
            'email' => $email,
            'permissions' => $permissions,
        ]);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::CREATE_SUBUSER,
            [
                'email' => $email,
                'permissions_count' => count($permissions),
            ]
        );

        return $result->toArray();
    }

    /**
     * Aktualizuje uprawnienia subusera
     */
    public function updateSubuserPermissions(
        Server $server,
        UserInterface $user,
        string $subuserUuid,
        array $permissions
    ): array
    {
        $result = $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->post("servers/{$server->getPterodactylServerIdentifier()}/users/{$subuserUuid}", [
                'permissions' => $permissions,
            ]);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::UPDATE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
                'permissions_count' => count($permissions),
            ]
        );

        return $result->toArray();
    }

    /**
     * Usuwa subusera z serwera
     */
    public function deleteSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid
    ): void
    {
        $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->delete("servers/{$server->getPterodactylServerIdentifier()}/users/{$subuserUuid}");

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
            ]
        );
    }

    /**
     * Pobiera szczegóły konkretnego subusera
     */
    public function getSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid
    ): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->get("servers/{$server->getPterodactylServerIdentifier()}/users/{$subuserUuid}")
            ->toArray();
    }
}
