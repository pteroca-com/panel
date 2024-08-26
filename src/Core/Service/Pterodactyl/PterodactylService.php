<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylService
{
    private ?string $url;

    private ?string $apiKey;

    private bool $isConfigured = false;

    private PterodactylApi $api;

    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    private function setCredentials(): void
    {
        $this->url = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        $this->apiKey = $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value);

        if (empty($this->url) || empty($this->apiKey)) {
            throw new \Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }

        $this->isConfigured = true;
    }

    private function connect(): void
    {
        if (!$this->isConfigured) {
            $this->setCredentials();
        }
        $this->api = new PterodactylApi($this->url, $this->apiKey);
    }

    public function getApi(): PterodactylApi
    {
        if (empty($this->api)) {
            $this->connect();
        }
        return $this->api;
    }
}
