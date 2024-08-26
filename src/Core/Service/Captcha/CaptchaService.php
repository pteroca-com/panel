<?php

namespace App\Core\Service\Captcha;

use App\Core\Enum\SettingEnum;
use App\Core\Provider\Captcha\CaptchaProviderInterface;
use App\Core\Service\SettingService;

readonly class CaptchaService
{
    public function __construct(
        private SettingService $settingService,
        private CaptchaProviderInterface $captchaProvider,
    ) {}

    public function isCaptchaEnabled(): bool
    {
        return (bool) $this->settingService->getSetting(SettingEnum::GOOGLE_CAPTCHA_VERIFICATION->value);
    }

    public function validateCaptcha(string $captchaResponse): bool
    {
        return $this->captchaProvider->validateCaptcha($captchaResponse);
    }
}