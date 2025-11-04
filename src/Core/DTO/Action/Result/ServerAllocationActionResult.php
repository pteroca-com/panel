<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Entity\Server;

readonly class ServerAllocationActionResult
{
    public function __construct(
        public bool    $success,
        public Server  $server,
        public ?string $error = null
    )
    {
    }
}
