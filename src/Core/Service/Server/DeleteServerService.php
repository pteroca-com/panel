<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Psr\Log\LoggerInterface;

class DeleteServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerLogService $serverLogService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function deleteServer(Server $entityInstance): void
    {
        try {
            $this->pterodactylService->getApi()->servers->delete($entityInstance->getPterodactylServerId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete pterodactyl server during deleting entity', [
                'exception' => $e,
                'serverId' => $entityInstance->getPterodactylServerId(),
                'entityId' => $entityInstance->getId(),
            ]);
        }

        $this->serverLogService->deleteServerActionLogs($entityInstance);
    }
}
