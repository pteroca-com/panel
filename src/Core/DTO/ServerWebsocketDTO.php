<?php

namespace App\Core\DTO;

use App\Core\Entity\Server;

readonly class ServerWebsocketDTO
{
    public function __construct(
        private ?string $token = null,
        private ?string $socket = null,
        private ?Server $server = null,
    ) {}

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getSocket(): ?string
    {
        return $this->socket;
    }

    public function getServer(): ?Server
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
