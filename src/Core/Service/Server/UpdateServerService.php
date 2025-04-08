<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Service\Pterodactyl\PterodactylService;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class UpdateServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerBuildService $serverBuildService,
    )
    {
    }

    public function updateServer(Server|ServerProduct $entityInstance): void
    {
        $pterodactylServerId = $entityInstance instanceof Server
            ? $entityInstance->getPterodactylServerId()
            : $entityInstance->getServer()->getPterodactylServerId();
        $pterodactylServer = $this->getPterodactylServerDetails($pterodactylServerId);

        switch (true) {
            case $entityInstance instanceof Server:
                $this->updateByServerEntity($entityInstance, $pterodactylServer);
                break;
            case $entityInstance instanceof ServerProduct:
                $this->updateByServerProductEntity($entityInstance, $pterodactylServer);
                break;
            default:
                throw new \InvalidArgumentException('Invalid entity type');
        }
    }

    private function updateByServerProductEntity(
        ServerProduct $entityInstance,
        PterodactylServer $pterodactylServer
    ): void
    {
        $updatedServerBuild = $this->serverBuildService
            ->prepareUpdateServerBuild($entityInstance, $pterodactylServer);

        $this->pterodactylService
            ->getApi()
            ->servers
            ->updateBuild($entityInstance->getServer()->getPterodactylServerId(), $updatedServerBuild);

        try {
            $updatedServerStartup = $this->serverBuildService
                ->prepareUpdateServerStartup($entityInstance, $pterodactylServer);

            $this->pterodactylService
                ->getApi()
                ->servers
                ->updateStartup($entityInstance->getServer()->getPterodactylServerId(), $updatedServerStartup);
        } catch (\Exception $exception) {

        }

        $this->updateByServerEntity($entityInstance->getServer(), $pterodactylServer);
    }

    private function updateByServerEntity(Server $entityInstance, PterodactylServer $pterodactylServer): void
    {
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
