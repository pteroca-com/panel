<?php

namespace App\Core\Service\Email;

use App\Core\Service\Pterodactyl\PterodactylRedirectService;

class ClientPanelUrlResolverService
{
    public function __construct(
        private readonly PterodactylRedirectService $pterodactylRedirectService,
    ) {}

    public function resolve(): string
    {
        return $this->pterodactylRedirectService->getBasePanelUrl();
    }
}
