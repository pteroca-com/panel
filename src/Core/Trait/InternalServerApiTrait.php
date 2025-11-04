<?php

namespace App\Core\Trait;

use App\Core\Entity\Server;
use App\Core\Enum\ServerPermissionEnum;
use Exception;

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
            return $server;
        }

        try {
            $pterodactylServer = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->servers()
                ->getServer($server->getPterodactylServerId(), [
                    'subusers'
                ]);
        } catch (Exception) {
            throw $this->createNotFoundException();
        }

        $permissions = $this->getServerPermissions($pterodactylServer, $server, $this->getUser());
        if (false === $permissions->hasPermission($permission)) {
            throw $this->createAccessDeniedException();
        }

        return $server;
    }
}
