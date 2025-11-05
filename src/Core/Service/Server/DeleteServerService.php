<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use Psr\Log\LoggerInterface;

readonly class DeleteServerService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private ServerLogService              $serverLogService,
        private LoggerInterface               $logger,
    )
    {
    }

    public function deleteServer(Server $entityInstance): void
    {
        try {
            $this->pterodactylApplicationService
                ->getApplicationApi()
                ->servers()
                ->deleteServer($entityInstance->getPterodactylServerId());
        } catch (Exception $e) {
            $this->logger->error('Failed to delete pterodactyl server during deleting entity', [
                'exception' => $e,
                'serverId' => $entityInstance->getPterodactylServerId(),
                'entityId' => $entityInstance->getId(),
            ]);
        }

        $this->serverLogService->deleteServerActionLogs($entityInstance);
    }
}
