<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Authorization\RegistrationService;
use App\Core\Service\SettingService;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinishConfigurationService
{
    private const REQUIRED_SETTINGS_MAP = [
        SettingEnum::SITE_URL->value => 'site_url',
        SettingEnum::SITE_TITLE->value => 'site_title',
        SettingEnum::LOCALE->value => 'site_locale',
        SettingEnum::PTERODACTYL_PANEL_URL->value => 'pterodactyl_panel_url',
        SettingEnum::PTERODACTYL_API_KEY->value => 'pterodactyl_panel_api_key',
        SettingEnum::CURRENCY_NAME->value => 'currency',
        SettingEnum::INTERNAL_CURRENCY_NAME->value => 'internal_currency_name',
    ];

    private const OPTIONAL_SETTINGS_MAP = [
        SettingEnum::EMAIL_SMTP_SERVER->value => 'email_smtp_server',
        SettingEnum::EMAIL_SMTP_PORT->value => 'email_smtp_port',
        SettingEnum::EMAIL_SMTP_USERNAME->value => 'email_smtp_username',
        SettingEnum::EMAIL_SMTP_PASSWORD->value => 'email_smtp_password',
        SettingEnum::EMAIL_SMTP_FROM->value => 'email_smtp_from',
        SettingEnum::STRIPE_SECRET_KEY->value => 'stripe_secret_key',
    ];

    public function __construct(
        private readonly SettingService $settingService,
        private readonly EmailConnectionVerificationService $emailConnectionVerificationService,
        private readonly PterodactylConnectionVerificationService $pterodactylConnectionVerificationService,
        private readonly RegistrationService $registrationService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function getRequiredSettingsMap(): array
    {
        return self::REQUIRED_SETTINGS_MAP;
    }

    public function finishConfiguration(array $data): ConfiguratorVerificationResult
    {
        $isEmailConnectionValidated = $this->emailConnectionVerificationService->validateConnection(
            $data['email_smtp_username'],
            $data['email_smtp_password'],
            $data['email_smtp_server'],
            $data['email_smtp_port'],
        );
        if (!$isEmailConnectionValidated->isVerificationSuccessful) {
            $data = $this->clearEmailSettings($data);
        }

        if (!empty($data['useExistingPterodactylSettings']) && $data['useExistingPterodactylSettings'] === 'true') {
            $data['pterodactyl_panel_url'] = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
            $data['pterodactyl_panel_api_key'] = $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value);
        }

        $isPterodactylConnectionValid = $this->validatePterodactylConnection($data['pterodactyl_panel_url'], $data['pterodactyl_panel_api_key']);
        if (!$isPterodactylConnectionValid) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_api_error'),
            );
        }

        $this->saveConfigurationSettings($data);

        if (!$this->createAdminAccount($data)) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.validation_error'),
            );
        }

        $this->disableConfigurator();

        return new ConfiguratorVerificationResult(true);
    }

    private function saveConfigurationSettings(array $data): void
    {
        $settingsMap = array_merge(self::REQUIRED_SETTINGS_MAP, self::OPTIONAL_SETTINGS_MAP);

        foreach ($settingsMap as $setting => $key) {
            $preparedValue = $data[$key] ?? '';
            $preparedValue = is_string($preparedValue) ? trim($preparedValue) : $preparedValue;
            $this->settingService->saveSetting($setting, $preparedValue);
        }
    }

    private function createAdminAccount(array $data): bool
    {
        if (empty($data['admin_email']) || empty($data['admin_password'])) {
            return false;
        }

        $user = new User();
        $user->setName('Admin');
        $user->setSurname('Admin');
        $user->setEmail($data['admin_email']);

        $registerResult = $this->registrationService->registerUser(
            $user,
            $data['admin_password'],
            [UserRoleEnum::ROLE_ADMIN->name],
            true,
        );

        return $registerResult->success;
    }

    private function disableConfigurator(): void
    {
        $this->settingService->saveSetting(SettingEnum::IS_CONFIGURED->value, '1');
    }

    private function clearEmailSettings(array $data): array
    {
        return array_diff_key($data, array_flip([
            'email_smtp_server',
            'email_smtp_port',
            'email_smtp_username',
            'email_smtp_password',
            'email_smtp_from',
        ]));
    }

    private function validatePterodactylConnection(string $url, string $apiKey): bool
    {
        $result = $this->pterodactylConnectionVerificationService->validateConnection($url, $apiKey);

        return $result->isVerificationSuccessful;
    }
}
