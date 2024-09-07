<?php

namespace App\Core\Handler;

use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

readonly class DeleteInactiveServersHandler implements HandlerInterface
{

    public function __construct(
        private ServerRepository $serverRepository,
        private PterodactylService $pterodactylService,
    ) {}

    public function handle(): void
    {
       $this->handleDeleteInactiveServers();
    }

    private function handleDeleteInactiveServers(): void
    {
        $serversToDelete = $this->serverRepository->getServersExpiredBefore(new \DateTime('now - 3 months'));
        foreach ($serversToDelete as $server) {
            $this->pterodactylService->getApi()->servers->delete($server->getPterodactylServerId());
            $this->serverRepository->delete($server);
        }
    }
}