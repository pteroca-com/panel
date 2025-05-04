<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Entity\Server;

class ServerAllocationActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly Server $server,
        public readonly ?string $error = null
    )
    {
    }
}
