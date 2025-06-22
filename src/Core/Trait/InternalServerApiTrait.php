<?php

namespace App\Core\Trait;

use App\Core\Entity\Server;
use App\Core\Enum\ServerPermissionEnum;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

trait InternalServerApiTrait
{
    use ServerPermissionsTrait;

    private function getServer(int $id, ServerPermissionEnum $permission): Server
    {
        $server = $this->serverRepository->find($id);
        if (empty($server)) {
            throw $this->createNotFoundException();
        }

        $isOwner = $server->getUser() === $this->getUser();
        if ($isOwner) {
            return $server; // TODO zwracac pterodactyl server, optymalizacja zapytan api itp.
        }

        /** @var PterodactylServer $pterodactylServer */
        $pterodactylServer = $this->pterodactylService
            ->getApi()
            ->servers
            ->get($server->getPterodactylServerId(), [
                'include' => ['subusers'],
            ]);
        if (empty($pterodactylServer)) {
            throw $this->createNotFoundException();
        }

        $permissions = $this->getServerPermissions($pterodactylServer, $server, $this->getUser());
        if (false === $permissions->hasPermission($permission)) {
            throw $this->createAccessDeniedException();
        }

        return $server;
    }
}
