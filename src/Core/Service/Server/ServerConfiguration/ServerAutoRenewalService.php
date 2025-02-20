<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;

class ServerAutoRenewalService
{
    public function __construct(
        private readonly ServerRepository $serverRepository,
    )
    {
    }

    public function toggleAutoRenewal(Server $server, bool $toggle): void
    {
        $server->setAutoRenewal($toggle);
        $this->serverRepository->save($server);
    }
}
