<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerReinstallationService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylService                $pterodactylService,
        private readonly ServerConfigurationStartupService $serverConfigurationStartupService,
    )
    {
        parent::__construct($this->pterodactylService);
    }

    public function reinstallServer(Server $server, ?int $selectedEgg): void
    {
        $serverDetails = $this->getServerDetails($server, ['egg']);
        if ($selectedEgg && $selectedEgg !== $serverDetails['egg']) {
            $this->validateEgg($server, $selectedEgg);
            $startupPayload = $this->serverConfigurationStartupService
                ->getStartupPayload('egg', $selectedEgg, $serverDetails);

            $this->serverConfigurationStartupService->updateServerStartup($server, $startupPayload);
        }

        $this->pterodactylService
            ->getApi()
            ->servers
            ->reinstall($server->getPterodactylServerId());
    }

    private function validateEgg(Server $server, int $selectedEgg): void
    {
        if (!in_array($selectedEgg, $server->getServerProduct()->getEggs())) {
            throw new \Exception('Invalid egg');
        }
    }
}
