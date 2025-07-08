<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerReinstallationService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylClientService          $pterodactylClientService,
        private readonly PterodactylService                $pterodactylService,
        private readonly ServerConfigurationStartupService $serverConfigurationStartupService,
    )
    {
        parent::__construct($this->pterodactylService);
    }

    public function reinstallServer(Server $server, UserInterface $user, ?int $selectedEgg): void
    {
        $serverDetails = $this->getServerDetails($server, ['egg']);
        if ($selectedEgg && $selectedEgg !== $serverDetails['egg']) {
            $this->validateEgg($server, $selectedEgg);
            $startupPayload = $this->serverConfigurationStartupService
                ->getStartupPayload('egg', $selectedEgg, $serverDetails);

            $this->serverConfigurationStartupService->updateServerStartup($server, $startupPayload);
        }

        try {
            $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->post("servers/{$server->getPterodactylServerIdentifier()}/settings/reinstall");
        } catch (\Exception $e) {
            if ($e->getMessage() !== '[]') {
                throw new \Exception('Failed to reinstall server: ' . $e->getMessage());
            }
        }
    }

    private function validateEgg(Server $server, int $selectedEgg): void
    {
        if (!in_array($selectedEgg, $server->getServerProduct()->getEggs())) {
            throw new \Exception('Invalid egg');
        }
    }
}
