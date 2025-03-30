<?php

namespace App\Core\Trait;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;

trait InternalServerApiTrait
{
    private function getServer(int $id): Server
    {
        $server = $this->serverRepository->find($id);
        if (empty($server)) {
            throw $this->createNotFoundException();
        }

        if ($server->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $server;
    }
}
