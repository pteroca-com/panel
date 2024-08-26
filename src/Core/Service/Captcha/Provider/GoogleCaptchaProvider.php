<?php

namespace App\Core\Service\Captcha\Provider;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

readonly class GoogleCaptchaProvider implements CaptchaProviderInterface
{
    public function __construct(
        private SettingService $settingService,
    ) {}

    public function validateCaptcha(string $captchaResponse): bool
    {
        $captchaSecretKey = $this->settingService->getSetting(SettingEnum::GOOGLE_CAPTCHA_SECRET_KEY->value);
        $verificationUrl = sprintf(
            "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
            $captchaSecretKey,
            $captchaResponse,
        );
        $response = file_get_contents($verificationUrl);
        $responseKeys = json_decode($response, true);
        return $responseKeys['success'] ?? false;
    }
}