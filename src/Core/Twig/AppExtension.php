<?php

namespace App\Core\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Core\Enum\SettingEnum;
use App\Core\DTO\TemplateOptionsDTO;
use App\Core\Service\SettingService;
use App\Core\Trait\FormatBytesTrait;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Service\Template\TemplateManager;
use Symfony\Component\Routing\RouterInterface;
use App\Core\Service\System\SystemVersionService;

class AppExtension extends AbstractExtension
{
    use FormatBytesTrait;

    public function __construct(
        private readonly SystemVersionService $systemVersionService,
        private readonly SettingService $settingService,
        private readonly TemplateManager $templateManager,
        private readonly Packages $packages,
        private readonly RouterInterface $router,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_currency', [$this, 'getCurrency']),
            new TwigFunction('get_default_theme_colors', [$this, 'getDefaultThemeColors']),
            new TwigFunction('get_app_version', [$this, 'getAppVersion']),
            new TwigFunction('get_logo', [$this, 'getLogo']),
            new TwigFunction('get_title', [$this, 'getTitle']),
            new TwigFunction('get_site_url', [$this, 'getSiteUrl']),
            new TwigFunction('show_email_verification_alert', [$this, 'showEmailVerificationAlert']),
            new TwigFunction('get_captcha_site_key', [$this, 'getCaptchaSiteKey']),
            new TwigFunction('get_favicon', [$this, 'getFavicon']),
            new TwigFunction('use_pterodactyl_panel_as_client_panel', [$this, 'usePterodactylPanelAsClientPanel']),
            new TwigFunction('get_pterodactyl_panel_url', [$this, 'getPterodactylPanelUrl']),
            new TwigFunction('is_pterodactyl_sso_enabled', [$this, 'isPterodactylSSOEnabled']),
            new TwigFunction('template_asset', [$this, 'templateAsset']),
            new TwigFunction('get_current_template_options', [$this, 'getCurrentTemplateOptions']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
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

    public function getDefaultThemeColors(): array
    {
        $themeSettings = [
            SettingEnum::DEFAULT_THEME_PRIMARY_COLOR->value,
            SettingEnum::DEFAULT_THEME_SECONDARY_COLOR->value,
            SettingEnum::DEFAULT_THEME_BACKGROUND_COLOR->value,
            SettingEnum::DEFAULT_THEME_LINK_COLOR->value,
            SettingEnum::DEFAULT_THEME_LINK_HOVER_COLOR->value,
            SettingEnum::DEFAULT_THEME_DARK_PRIMARY_COLOR->value,
            SettingEnum::DEFAULT_THEME_DARK_SECONDARY_COLOR->value,
            SettingEnum::DEFAULT_THEME_DARK_BACKGROUND_COLOR->value,
            SettingEnum::DEFAULT_THEME_DARK_LINK_COLOR->value,
            SettingEnum::DEFAULT_THEME_DARK_LINK_HOVER_COLOR->value,
        ];

        $settings = [];
        foreach ($themeSettings as $setting) {
            $settings[$setting] = $this->settingService->getSetting($setting);
        }

        return $settings;
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

    public function showEmailVerificationAlert(): bool
    {
        $emailVerificationValue = $this->settingService
            ->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);

        return in_array($emailVerificationValue, [
            EmailVerificationValueEnum::REQUIRED->value,
            EmailVerificationValueEnum::OPTIONAL->value,
        ]);
    }

    public function usePterodactylPanelAsClientPanel(): bool
    {
        return (bool)$this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value);
    }

    public function isPterodactylSSOEnabled(): bool
    {
        return (bool)$this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_ENABLED->value);
    }

    public function getPterodactylPanelUrl(string $path = ''): string
    {
        $isPterodactylSSOEnabled = $this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_ENABLED->value);
        $pterodactylPanelUrl = $isPterodactylSSOEnabled
            ? $this->router->generate('sso_redirect')
            : $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);

        if (!empty($path)) {
            if (!$isPterodactylSSOEnabled) {
                $pterodactylPanelUrl = rtrim($pterodactylPanelUrl, '/') . '/' . ltrim($path, '/');
            } else {
                $pterodactylPanelUrl .= '?redirect_path=' . $path;
            }
        }

        return $pterodactylPanelUrl;
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
        return $this->systemVersionService->getCurrentVersion();
    }

    public function templateAsset(string $path): string
    {
        $currentTheme = $this->settingService->getSetting(SettingEnum::CURRENT_THEME->value);
        $path = sprintf('/assets/theme/%s/%s', $currentTheme, $path);

        return $this->packages->getUrl($path);
    }

    public function getCurrentTemplateOptions(): TemplateOptionsDTO
    {
        return $this->templateManager->getCurrentTemplateOptions();
    }
}
