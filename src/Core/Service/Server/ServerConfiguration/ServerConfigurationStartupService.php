<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

class ServerConfigurationStartupService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
    )
    {
    }

    public function updateServerStartup(
        Server $server,
        array $startupPayload,
    ): void
    {
        $this->pterodactylApplicationService
            ->getApplicationApi()
            ->servers()
            ->updateServerStartup(
                $server->getPterodactylServerId(),
                $startupPayload,
            );
    }

    public function getStartupPayload(string $variableKey, string $variableValue, array $serverDetails): array
    {
        $preparedPayload = [
            'startup' => $serverDetails['container']['startup_command'],
            'egg' => $serverDetails['egg'],
            'environment' => $serverDetails['container']['environment'],
            'image' => $serverDetails['container']['image'],
            'skip_scripts' => false,
        ];

        if (!isset($preparedPayload[$variableKey])) {
            throw new \Exception('Invalid variable key');
        }

        $preparedPayload[$variableKey] = $variableValue;

        return $preparedPayload;
    }
}
