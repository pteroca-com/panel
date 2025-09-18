<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\Pterodactyl\Application\PterodactylAdapterInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylClientAdapterInterface;
use App\Core\Contract\UserInterface;
use App\Core\DTO\Pterodactyl\Credentials;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

class PterodactylApplicationService
{
    private PterodactylAdapterInterface $applicationCachedAdapter;

    private array $clientCachedAdapters = [];

    public function __construct(
        private readonly SettingService $settingService,
        private readonly PterodactylAdapterInterface $pterodactylAdapter,
        private readonly PterodactylClientAdapterInterface $pterodactylClientAdapter,
    ) {
    }

    public function getApplicationApi(): PterodactylAdapterInterface
    {
        if (isset($this->applicationCachedAdapter)) {
            return $this->applicationCachedAdapter;
        }

        $applicationCredentials = new Credentials(
            $this->getApiUrl(),
            $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value),
        );
        $this->pterodactylAdapter->setCredentials($applicationCredentials);
        $this->applicationCachedAdapter = $this->pterodactylAdapter;

        return $this->pterodactylAdapter;
    }

    public function getClientApi(UserInterface $user): PterodactylClientAdapterInterface
    {
        if (isset($this->clientCachedAdapters[$user->getId()])) {
            return $this->clientCachedAdapters[$user->getId()];
        }

        $userCredentials = new Credentials(
            $this->getApiUrl(),
            $user->getPterodactylUserApiKey(),
        );
        $this->pterodactylClientAdapter->setCredentials($userCredentials);
        $this->clientCachedAdapters[$user->getId()] = $this->pterodactylClientAdapter;

        return $this->pterodactylClientAdapter;
    }

    private function getApiUrl(): string
    {
        return $this->settingService
            ->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
    }
}
