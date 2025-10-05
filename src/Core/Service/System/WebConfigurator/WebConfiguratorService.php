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
        private readonly UserValidationService $userValidationService,
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
        if (!empty($data['language'])) {
            $this->translator->setLocale($data['language']);
        }

        if (!isset($data['step'])) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.invalid_step'),
            );
        }

        return match ((int) $data['step']) {
            2 => $this->validatePterodactylStep($data),
            3 => $this->emailConnectionVerificationService->validateConnection(
                $data['email_smtp_username'],
                $data['email_smtp_password'],
                $data['email_smtp_server'],
                $data['email_smtp_port'],
            ),
            5 => $this->validateAdminAccountStep($data),
            default => new ConfiguratorVerificationResult(true),
        };
    }

    private function validatePterodactylStep(array $data): ConfiguratorVerificationResult
    {
        if (!empty($data['checkExistingSettings']) && $data['checkExistingSettings'] === 'true') {
            return $this->pterodactylConnectionVerificationService->validateExistingConnection();
        }

        return $this->pterodactylConnectionVerificationService->validateConnection(
            $data['pterodactyl_panel_url'] ?? '',
            $data['pterodactyl_panel_api_key'] ?? '',
        );
    }

    private function validateAdminAccountStep(array $data): ConfiguratorVerificationResult
    {
        $useExistingPterodactylSettings = !empty($data['useExistingPterodactylSettings']) 
            && $data['useExistingPterodactylSettings'] === 'true';
        $pterodactylPanelUrl = !$useExistingPterodactylSettings 
            ? $data['pterodactyl_panel_url'] 
            : $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        $pterodactylApiKey = !$useExistingPterodactylSettings
            ? $data['pterodactyl_panel_api_key']
            : $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value);

        return $this->userValidationService->validateUserDoesNotExist(
            $data['admin_email'],
            $pterodactylPanelUrl,
            $pterodactylApiKey,
        );
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

        return true;
    }
}
