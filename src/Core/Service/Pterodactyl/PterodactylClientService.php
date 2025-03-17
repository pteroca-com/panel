<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Exception\UserDoesNotHaveClientApiKeyException;
use App\Core\Service\SettingService;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylClientService
{
    private ?string $url;

    private bool $isConfigured = false;

    private PterodactylApi $api;

    public function __construct(
        private readonly SettingService $settingService
    ) {
    }

    private function setCredentials(): void
    {
        $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value) ?? '';
        $this->url = rtrim($pterodactylUrl, '/');
        $this->isConfigured = true;
    }

    private function connect(string $apiKey): void
    {
        if (!$this->isConfigured) {
            $this->setCredentials();
        }
        $this->api = new PterodactylApi($this->url, $apiKey, 'client');
    }

    public function getApi(User $user): PterodactylApi
    {
        if (empty($user->getPterodactylUserApiKey())) {
            throw new UserDoesNotHaveClientApiKeyException();
        }

        if (empty($this->api)) {
            $this->connect($user->getPterodactylUserApiKey());
        }

        return $this->api;
    }
}
