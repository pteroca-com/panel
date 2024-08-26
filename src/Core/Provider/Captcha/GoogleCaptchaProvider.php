<?php

namespace App\Core\Provider\Captcha;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

class GoogleCaptchaProvider implements CaptchaProviderInterface
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function validateCaptcha(string $captchaResponse): bool
    {
        $captchaSecretKey = $this->settingService->getSetting(SettingEnum::GOOGLE_CAPTCHA_SECRET_KEY->value);
        $verificationUrl = sprintf(
            "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
            $captchaSecretKey,
            $captchaResponse,
        );
        $response = $this->fileGetContents($verificationUrl);
        $responseKeys = json_decode($response, true);
        return $responseKeys['success'] ?? false;
    }

    protected function fileGetContents(string $url): string
    {
        return file_get_contents($url);
    }
}
