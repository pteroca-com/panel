<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use App\Core\Enum\SettingEnum;
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

    public function validateStep(array $data): ConfiguratorVerificationResult
    {
        if (!isset($data['step'])) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.errors.invalid_step'),
            );
        }

        return match ((int) $data['step']) {
            2 => $this->emailConnectionVerificationService->validateConnection($data),
            3 => $this->pterodactylConnectionVerificationService->validateConnection($data),
            default => new ConfiguratorVerificationResult(true),
        };
    }

    public function finishConfiguration(array $data): ConfiguratorVerificationResult
    {
        return $this->finishConfigurationService->finishConfiguration($data);
    }

    public function isConfiguratorEnabled(): bool
    {
        $isAlreadyConfigured = $this->settingService->getSetting(SettingEnum::IS_CONFIGURED->value);
        if (!empty($isAlreadyConfigured)) {
            return false;
        }

        foreach ($this->finishConfigurationService->getRequiredSettingsMap() as $settingName) {
            $settingValue = $this->settingService->getSetting($settingName);

            if (!empty($settingValue)) {
                //return false; // @todo: uncomment this line
            }
        }

        return true;
    }
}