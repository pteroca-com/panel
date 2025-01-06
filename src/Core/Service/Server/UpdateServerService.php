<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylService;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class UpdateServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
    )
    {
    }

    public function updateServer(Server $entityInstance): void
    {
        $pterodactylServer = $this->getPterodactylServerDetails($entityInstance->getPterodactylServerId());

        if ($entityInstance->getIsSuspended() !== $pterodactylServer->get('suspended')) {
            if ($entityInstance->getIsSuspended()) {
                $this->pterodactylService->getApi()->servers->suspend($entityInstance->getPterodactylServerId());
            } else {
                $this->pterodactylService->getApi()->servers->unsuspend($entityInstance->getPterodactylServerId());
            }
        }

        if ($entityInstance->getUser()->getPterodactylUserId() !== $pterodactylServer->get('user')) {
            $this->pterodactylService->getApi()->servers->updateDetails(
                $entityInstance->getPterodactylServerId(),
                [
                    'name' => $pterodactylServer->get('name'),
                    'description' => $pterodactylServer->get('description'),
                    'user' => $entityInstance->getUser()->getPterodactylUserId(),
                ],
            );
        }
    }

    private function getPterodactylServerDetails(int $serverId): PterodactylServer
    {
        /** @var PterodactylServer $server */
        $server = $this->pterodactylService->getApi()->servers->get($serverId);
        return $server;
    }
}
