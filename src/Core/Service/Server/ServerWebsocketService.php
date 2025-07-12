<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\ServerWebsocketDTO;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use Psr\Log\LoggerInterface;

class ServerWebsocketService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylService,
        private readonly LoggerInterface $logger,
    ) {}

    public function getWebsocketToken(Server $server, UserInterface $user): ?ServerWebsocketDTO
    {
        if ($server->getIsSuspended()) {
            return new ServerWebsocketDTO();
        }

        try {
            $websocketData = $this->pterodactylService
                ->getApi($user)
                ->servers
                ->websocket($server->getPterodactylServerIdentifier());
        } catch (\Exception $e) {
            $this->logger->error('Failed to get websocket token for server', [
                'server_id' => $server->getId(),
                'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new ServerWebsocketDTO();
        }

        return new ServerWebsocketDTO(
            $websocketData['data']['token'],
            $websocketData['data']['socket'],
            $server,
        );
    }
}
