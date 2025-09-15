<?php

namespace App\Core\Trait;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Collection\ServerPermissionCollection;
use App\Core\DTO\Pterodactyl\Application\PterodactylServer;
use App\Core\Entity\Server;
use App\Core\Enum\ServerPermissionEnum;
use App\Core\Enum\UserRoleEnum;

trait ServerPermissionsTrait
{
    private function getServerPermissions(
        PterodactylServer $pterodactylServer,
        Server $server,
        UserInterface $user
    ): ServerPermissionCollection {
        $isAdmin = !empty(array_filter(
            $user->getRoles(),
            fn($role) => $role === UserRoleEnum::ROLE_ADMIN->name,
        ));
        $isServerOwner = $server->getUser()->getId() === $user->getId();

        if (!$isAdmin && !$isServerOwner) {
            $subUser = current(array_filter(
                $pterodactylServer->get('relationships')['subusers']->toArray(),
                fn($subuser) => $subuser['user_id'] === $user->getPterodactylUserId(),
            ));

            return ServerPermissionEnum::fromArray($subUser['permissions'] ?? []);
        }

        $allPermissions = [];
        foreach (ServerPermissionEnum::cases() as $permission) {
            $allPermissions[] = $permission->value;
        }

        return ServerPermissionEnum::fromArray($allPermissions);
    }
}
