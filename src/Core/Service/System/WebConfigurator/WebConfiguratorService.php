<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\Service\LocaleService;
use App\Core\Service\SettingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebConfiguratorService
{
    public function __construct(
        private readonly LocaleService $localeService,
        private readonly EmailConnectionVerificationService $emailConnectionVerificationService,
        private readonly PterodactylConnectionVerificationService $pterodactylConnectionVerificationService,
        private readonly TranslatorInterface $translator,
        private readonly FinishConfigurationService $finishConfigurationService,
        private readonly SettingService $settingService,
    ) {}

    public function getDataForFirstConfiguration(Request $request): array
    {
        $language = $request->get('language') ?? $request->getLocale();
        $this->translator->setLocale($language);

        return [
            'availableLanguages' => $this->localeService->getAvailableLocales(),
            'currencies' => Currencies::getCurrencyCodes(),
            'configuratorLanguage' => $language,
            'currentSiteUrl' => $request->getSchemeAndHttpHost(),
        ];
    }

    public function validateStep(array $data): bool
    {
        if (!isset($data['step'])) {
            return false;
        }

        return match ((int) $data['step']) {
            2 => $this->emailConnectionVerificationService->validateConnection($data),
            3 => $this->pterodactylConnectionVerificationService->validateConnection($data),
            default => true,
        };
    }

    public function finishConfiguration(array $data): bool
    {
        return $this->finishConfigurationService->finishConfiguration($data);
    }

    public function isConfiguratorEnabled(): bool
    {
        foreach ($this->finishConfigurationService->getRequiredSettingsMap() as $settingName) {
            $settingValue = $this->settingService->getSetting($settingName);

            if (!empty($settingValue)) {
                //return false; // @todo: uncomment this line
            }
        }

        return true;
    }
}