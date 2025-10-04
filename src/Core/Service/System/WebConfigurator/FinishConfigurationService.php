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

        if (!empty($data['pterodactyl_panel_url']) && !empty($data['pterodactyl_panel_api_key'])) {
            $isPterodactylApiConnectionValidated = $this->pterodactylConnectionVerificationService->validateConnection(
                $data['pterodactyl_panel_url'],
                $data['pterodactyl_panel_api_key'],
            );
            if (!$isPterodactylApiConnectionValidated->isVerificationSuccessful) {
                return new ConfiguratorVerificationResult(
                    false,
                    $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_api_error'),
                );
            }
        } else {
            $data = $this->clearPterodactylSettings($data);
        }

        $this->saveConfigurationSettings($data);
        $this->createAdminAccount($data);
        $this->disableConfigurator();

        return new ConfiguratorVerificationResult(true);
    }

    private function saveConfigurationSettings(array $data): void
    {
        $settingsMap = array_merge(self::REQUIRED_SETTINGS_MAP, self::OPTIONAL_SETTINGS_MAP);

        foreach ($settingsMap as $setting => $key) {
            $this->settingService->saveSetting($setting, $data[$key] ?? '');
        }
    }

    private function createAdminAccount(array $data): void
    {
        if (empty($data['admin_email']) || empty($data['admin_password'])) {
            return;
        }

        $user = new User();
        $user->setName('Admin');
        $user->setSurname('Admin');
        $user->setEmail($data['admin_email']);

        $this->registrationService->registerUser(
            $user,
            $data['admin_password'],
            [UserRoleEnum::ROLE_ADMIN->name],
            true,
            false,
        );
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

    private function clearPterodactylSettings(array $data): array
    {
        return array_diff_key($data, array_flip([
            'pterodactyl_panel_url',
            'pterodactyl_panel_api_key',
        ]));
    }
}
