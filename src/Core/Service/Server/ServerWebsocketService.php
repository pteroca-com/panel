<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\ServerWebsocketDTO;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerWebsocketService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylService,
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
            // TODO log error
            return new ServerWebsocketDTO();
        }

        return new ServerWebsocketDTO(
            $websocketData['data']['token'],
            $websocketData['data']['socket'],
            $server,
        );
    }
}
