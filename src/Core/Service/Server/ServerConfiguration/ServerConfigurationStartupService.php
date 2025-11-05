<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;

readonly class ServerConfigurationStartupService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
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

    /**
     * @throws Exception
     */
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
            throw new Exception('Invalid variable key');
        }

        $preparedPayload[$variableKey] = $variableValue;

        return $preparedPayload;
    }

    public function getEnvironmentVariablePayload(string $variableKey, string $variableValue, array $serverDetails): array
    {
        $environment = $serverDetails['container']['environment'];
        $environment[$variableKey] = $variableValue;

        return [
            'startup' => $serverDetails['container']['startup_command'],
            'egg' => $serverDetails['egg'],
            'environment' => $environment,
            'image' => $serverDetails['container']['image'],
            'skip_scripts' => false,
        ];
    }
}
