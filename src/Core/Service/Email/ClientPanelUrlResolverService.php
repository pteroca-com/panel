<?php

namespace App\Core\Service\Email;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

readonly class ClientPanelUrlResolverService
{
    public function __construct(
        private SettingService $settingService,
    ) {}

    public function resolve(): string
    {
        $usePterodactylPanel = $this->settingService->getSetting(
            SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value
        );

        if ($usePterodactylPanel) {
            return $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        }

        return $this->settingService->getSetting(SettingEnum::SITE_URL->value);
    }
}
