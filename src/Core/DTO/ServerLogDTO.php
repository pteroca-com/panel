<?php

namespace App\Core\DTO;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogSourceTypeEnum;

class ServerLogDTO
{
    public function __construct(
        public ?int $id,
        public ServerLogSourceTypeEnum $sourceType,
        public string $actionId,
        public ?UserInterface $user,
        public Server $server,
        public \DateTimeInterface $createdAt,
        public ?string $details = null,
    ) {}
}