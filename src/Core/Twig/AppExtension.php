<?php

namespace App\Core\Twig;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_currency', [$this, 'getCurrency']),
            new TwigFunction('get_default_theme_primary_colors', [$this, 'getDefaultThemePrimaryColors']),
            new TwigFunction('get_app_version', [$this, 'getAppVersion']),
            new TwigFunction('get_logo', [$this, 'getLogo']),
            new TwigFunction('get_title', [$this, 'getTitle']),
            new TwigFunction('get_site_url', [$this, 'getSiteUrl']),
            new TwigFunction('get_require_email_verification', [$this, 'getRequireEmailVerification']),
            new TwigFunction('get_captcha_site_key', [$this, 'getCaptchaSiteKey']),
            new TwigFunction('get_favicon', [$this, 'getFavicon']),
        ];
    }

    public function getCurrency(): string
    {
        $currency = $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value);
        if (empty($currency)) {
            throw new \Exception('Internal currency is not set');
        }
        return $currency;
    }

    public function getDefaultThemePrimaryColors(): array
    {
        return [
            'light' => $this->settingService->getSetting(SettingEnum::DEFAULT_THEME_PRIMARY_COLOR->value),
            'dark' => $this->settingService->getSetting(SettingEnum::DEFAULT_THEME_DARK_PRIMARY_COLOR->value),
        ];
    }

    public function getLogo(): string
    {
        $uploadedLogo = $this->settingService->getSetting(SettingEnum::LOGO->value);
        if (!empty($uploadedLogo)) {
            return sprintf('/uploads/settings/%s', $uploadedLogo);
        }
        return '/assets/img/logo/logo.png';
    }

    public function getFavicon(): string
    {
        $uploadedFavicon = $this->settingService->getSetting(SettingEnum::SITE_FAVICON->value);
        if (!empty($uploadedFavicon)) {
            return sprintf('/uploads/settings/%s', $uploadedFavicon);
        }
        return '/assets/img/favicon/favicon.ico';
    }

    public function getTitle(): string
    {
        return $this->settingService->getSetting(SettingEnum::SITE_TITLE->value) ?? '';
    }

    public function getSiteUrl(): string
    {
        return $this->settingService->getSetting(SettingEnum::SITE_URL->value) ?? '';
    }

    public function getRequireEmailVerification(): bool
    {
        return (bool)$this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
    }

    public function getCaptchaSiteKey(): ?string
    {
        $isCaptchaEnabled = $this->settingService->getSetting(SettingEnum::GOOGLE_CAPTCHA_VERIFICATION->value);
        if (empty($isCaptchaEnabled)) {
            return null;
        }
        return $this->settingService->getSetting(SettingEnum::GOOGLE_CAPTCHA_SITE_KEY->value);
    }

    public function getAppVersion(): string
    {
        return '0.2.2';
    }
}