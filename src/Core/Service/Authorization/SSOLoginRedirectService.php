<?php

namespace App\Core\Service\Authorization;

use App\Core\Contract\UserInterface;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Firebase\JWT\JWT;

class SSOLoginRedirectService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function createSSOToken(UserInterface $user): string
    {
        $payload = [
            'iss' => $this->settingService->getSetting(SettingEnum::SITE_URL->value),
            'aud' => $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value),
            'iat' => time(),
            'exp' => time() + 60,
            'user' => [
                'id' => $user->getPterodactylUserId(),
            ],
        ];

        $pterodactylSsoSecret = $this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_SECRET->value);
        if (empty($pterodactylSsoSecret)) {
            throw new \Exception('PTERODACTYL_SSO_SECRET is not set');
        }

        return JWT::encode($payload, $pterodactylSsoSecret, 'HS256');
    }

    public function getPterodactylLoginUrl(): string
    {
        $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        if (empty($pterodactylUrl)) {
            throw new \Exception('PTERODACTYL_PANEL_URL is not set');
        }

        return sprintf('%s/pteroca/authorize', $pterodactylUrl);
    }
}
