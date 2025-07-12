<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerConfigurationOptionService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylService                $pterodactylService,
        private readonly ServerConfigurationStartupService $serverConfigurationStartupService,
    ) {
        parent::__construct($this->pterodactylService);
    }

    public function updateServerStartupOption(
        Server $server,
        UserInterface $user,
        string $variableKey,
        string $variableValue,
    ): void
    {
        $variableKey = $this->mapVariableKey($variableKey);
        $serverDetails = $this->getServerDetails($server, ['egg']);
        $this->validateVariableValue($variableKey, $variableValue, $serverDetails);

        $startupPayload = $this->serverConfigurationStartupService
            ->getStartupPayload($variableKey, $variableValue, $serverDetails);

        $this->serverConfigurationStartupService
            ->updateServerStartup($server, $startupPayload);
    }

    private function mapVariableKey(string $variableKey): string
    {
        return match ($variableKey) {
            'startup' => 'startup',
            'docker_image' => 'image',
            default => throw new \Exception('Invalid variable key'),
        };
    }

    private function validateVariableValue(string $variableKey, string $variableValue, array $serverDetails): void
    {
        if (empty($variableValue)) {
            throw new \Exception('Invalid variable value');
        }

        if ($variableKey === 'image') {
            $availableImages = $serverDetails['relationships']['egg']->get('docker_images');
            $selectedImage = array_filter($availableImages, fn($image) => $image === $variableValue);
            if (empty($selectedImage)) {
                throw new \Exception('Invalid image');
            }
        }
    }
}
