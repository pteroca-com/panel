<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Entity\ServerSubuser;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerUserService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylService $pterodactylService,
        private readonly ServerLogService $serverLogService,
        private readonly ServerSubuserRepository $serverSubuserRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function getAllSubusers(Server $server, UserInterface $user): array
    {
        return $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->get(sprintf('servers/%s/users', $server->getPterodactylServerIdentifier()))
            ->toArray();
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
            $existingPterodactylUsers = $pterodactylApi->users->all(['filter[email]' => $email]);
            $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);

            if (count($existingPterodactylUsers->toArray()) === 0 || !$existingPterocaUser) {
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

            $this->syncServerSubuser($server, $existingPterocaUser, $permissions);

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

        } catch (\Exception $e) { // TODO translations
            if (str_contains($e->getMessage(), 'No user found') ||
                str_contains($e->getMessage(), 'does not exist')) {
                throw new \Exception(sprintf('User with email %s does not exist in the system. The user must register first.', $email));
            }
            
            if (str_contains($e->getMessage(), 'already assigned') ||
                str_contains($e->getMessage(), 'already exists')) {
                throw new \Exception(sprintf('User with email %s is already added to this server.', $email));
            }

            throw $e;
        }
    }

    public function updateSubuserPermissions(
        Server $server,
        UserInterface $user,
        string $subuserUuid,
        string $email,
        array $permissions
    ): array
    {
        $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$existingPterocaUser) {
            throw new \Exception(sprintf('User with email %s does not exist in the system.', $email));
        }

        $this->validateSubuserModification($server, $user, $email, 'modify permissions');

        $result = $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->post(sprintf('servers/%s/users/%s', $server->getPterodactylServerIdentifier(), $subuserUuid), [
                'permissions' => $permissions,
            ]);

        $this->syncServerSubuser($server, $existingPterocaUser, $permissions);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::UPDATE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
                'subuser_email' => $email,
                'permissions_count' => count($permissions),
            ]
        );

        return $result->toArray();
    }

    public function deleteSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid,
        string $email
    ): void
    {
        $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$existingPterocaUser) {
            throw new \Exception(sprintf('User with email %s does not exist in the system.', $email));
        }

        $this->validateSubuserModification($server, $user, $email, 'delete yourself from the server');

        $this->pterodactylClientService
            ->getApi($user)
            ->http
            ->delete(sprintf('servers/%s/users/%s', $server->getPterodactylServerIdentifier(), $subuserUuid));

        $existingSubuser = $this->serverSubuserRepository->findSubuserByServerAndUser($server, $existingPterocaUser);
        if ($existingSubuser) {
            $this->serverSubuserRepository->delete($existingSubuser);
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
                'subuser_email' => $email,
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

    private function syncServerSubuser(Server $server, UserInterface $subuserEntity, array $permissions): void
    {
        $existingSubuser = $this->serverSubuserRepository->findSubuserByServerAndUser($server, $subuserEntity);
        
        if ($existingSubuser) {
            $existingSubuser->setPermissions($permissions);
            $this->serverSubuserRepository->save($existingSubuser);
        } else {
            $serverSubuser = new ServerSubuser();
            $serverSubuser->setServer($server);
            $serverSubuser->setUser($subuserEntity);
            $serverSubuser->setPermissions($permissions);
            $this->serverSubuserRepository->save($serverSubuser);
        }
    }

    private function validateSubuserModification(Server $server, UserInterface $user, string $targetEmail, string $action): void
    {
        $isServerOwner = $server->getUser()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        
        if (!$isServerOwner && !$isAdmin && $user->getEmail() === $targetEmail) {
            // TODO translations
            throw new \Exception(sprintf('You cannot %s for yourself.', $action));
        }
    }
}
