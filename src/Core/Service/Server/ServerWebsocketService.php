<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\ServerWebsocketDTO;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use Psr\Log\LoggerInterface;

readonly class ServerWebsocketService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private LoggerInterface               $logger,
    ) {}

    public function getWebsocketToken(Server $server, UserInterface $user): ?ServerWebsocketDTO
    {
        if ($server->getIsSuspended()) {
            return new ServerWebsocketDTO();
        }

        try {
            $websocketData = $this->pterodactylApplicationService
                ->getClientApi($user)
                ->servers()
                ->getWebSocketToken($server->getPterodactylServerIdentifier());
        } catch (Exception $e) {
            $this->logger->error('Failed to get websocket token for server', [
                'server_id' => $server->getId(),
                'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new ServerWebsocketDTO();
        }

        return new ServerWebsocketDTO(
            $websocketData['token'],
            $websocketData['socket'],
            $server,
        );
    }
}
