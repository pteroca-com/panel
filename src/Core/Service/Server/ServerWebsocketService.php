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
            return null;
        }

        try {
            $websocketData = $this->pterodactylService
                ->getApi($user)
                ->servers
                ->websocket($server->getPterodactylServerIdentifier());
        } catch (\Exception $e) {
            return null;
        }

        return new ServerWebsocketDTO(
            $websocketData['data']['token'],
            $websocketData['data']['socket'],
            $server,
        );
    }
}
