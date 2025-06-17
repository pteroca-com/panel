<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerUserService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylService $pterodactylService,
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
            ->get(sprintf('servers/%s/users', $server->getPterodactylServerIdentifier()))
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

        $result = $pterodactylClientApi->http->post(sprintf('servers/%s/users', $server->getPterodactylServerIdentifier()), [
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

    public function addExistingUserToServer(
        Server $server,
        UserInterface $user,
        string $email,
        array $permissions = []
    ): array
    {
        $pterodactylClientApi = $this->pterodactylClientService->getApi($user);
        $pterodactylApi = $this->pterodactylService->getApi();

        try {
            $existingUsers = $pterodactylApi->users->all(['filter[email]' => $email]);

            if (count($existingUsers->toArray()) === 0) {
                throw new \Exception(sprintf('User with email %s does not exist in the system. The user must register first.', $email));
            }

            $currentSubusers = $this->getAllSubusers($server, $user);
            foreach ($currentSubusers['data'] ?? [] as $subuser) {
                if (isset($subuser['attributes']['email']) && $subuser['attributes']['email'] === $email) {
                    throw new \Exception(sprintf('User with email %s is already added to this server.', $email));
                }
            }

            $result = $pterodactylClientApi->http->post(sprintf('servers/%s/users', $server->getPterodactylServerIdentifier()), [
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

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'No user found') !== false || 
                strpos($e->getMessage(), 'does not exist') !== false) {
                throw new \Exception(sprintf('User with email %s does not exist in the system. The user must register first.', $email));
            }
            
            if (strpos($e->getMessage(), 'already assigned') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                throw new \Exception(sprintf('User with email %s is already added to this server.', $email));
            }

            throw $e;
        }
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
            ->post(sprintf('servers/%s/users/%s', $server->getPterodactylServerIdentifier(), $subuserUuid), [
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

    public function deleteSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid
    ): void
    {
        $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->delete(sprintf('servers/%s/users/%s', $server->getPterodactylServerIdentifier(), $subuserUuid));

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
            ]
        );
    }

    public function getSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid
    ): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->get(sprintf('servers/%s/users/%s', $server->getPterodactylServerIdentifier(), $subuserUuid))
            ->toArray();
    }
}
