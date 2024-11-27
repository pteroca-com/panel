<?php

namespace App\Core\DTO;

use App\Core\Entity\Server;

class ServerWebsocketDTO
{
    public function __construct(
        private readonly string $token,
        private readonly string $socket,
        private readonly Server $server,
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSocket(): string
    {
        return $this->socket;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'socket' => $this->socket,
        ];
    }
}
