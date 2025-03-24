<?php

namespace App\Core\Handler\Installer;

use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\SettingRepository;
use App\Core\Service\SettingService;
use App\Core\Service\System\EnvironmentConfigurationService;
use App\Core\Service\System\WebConfigurator\EmailConnectionVerificationService;
use App\Core\Service\System\WebConfigurator\PterodactylConnectionVerificationService;
use Symfony\Component\Console\Style\SymfonyStyle;

class SystemSettingConfiguratorHandler
{
    private bool $isSaved = false;

    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly SettingService $settingService,
        private readonly EnvironmentConfigurationService $environmentConfigurationHandler,
        private readonly EmailConnectionVerificationService $emailConnectionVerificationService,
        private readonly PterodactylConnectionVerificationService $pterodactylConnectionVerificationService,
    ) {}

    public function configureSystemSettings(SymfonyStyle $io): void
    {
        $isAlreadyConfigured = $this->settingService->getSetting(SettingEnum::IS_CONFIGURED->value);
        if ($isAlreadyConfigured) {
            $io->warning('System is already configured. Current settings will be overwritten.');
        }

        if ($io->ask('Do you want to configure system settings? (yes/no)', 'yes') === 'yes') {
            $this->generateAppSecretKeyIfNeeded($io);
            $this->askForSiteSettings($io);
            $this->askForPterodactylPanelCredentialsSettings($io);
            $this->askForEmailSettings($io);
            $this->askForPaymentSettings($io);
            $this->askForConfigureUser($io);

            if ($this->isSaved) {
                $this->settingService->saveSetting(SettingEnum::IS_CONFIGURED->value, '1');
            }
        }
    }

    private function askForConfigureUser(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to create user?', 'yes') === 'yes') {
            $io->section('User configuration');
            $io->text('Please provide user credentials');

            $email = $io->ask('User e-mail', '');
            $password = $io->ask('User password', '');
            $isAdmin = $io->ask('Is user admin? (yes/no)', 'yes') === 'yes';

            $userRole = $isAdmin ? UserRoleEnum::ROLE_ADMIN : UserRoleEnum::ROLE_USER;
            exec(sprintf('php bin/console app:create-new-user %s %s %s', $email, $password, $userRole->name));
        }
    }

    private function askForPaymentSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to configure payment settings? (yes/no)', 'yes') === 'yes') {
            $io->section('Payment settings');
            $io->text('Please provide payment settings');

            $settings = [
                SettingEnum::STRIPE_SECRET_KEY->value => [
                    'value' => $io->ask(
                        'Stripe Secret Key',
                        $this->settingRepository->getSetting(SettingEnum::STRIPE_SECRET_KEY),
                    ),
                ],
                SettingEnum::CURRENCY_NAME->value => [
                    'value' => $io->ask(
                        'Currency',
                        $this->settingRepository->getSetting(SettingEnum::CURRENCY_NAME),
                    ),
                ],
                SettingEnum::INTERNAL_CURRENCY_NAME->value => [
                    'value' => $io->ask(
                        'Internal Currency Name (balance)',
                        $this->settingRepository->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME),
                    ),
                ],
            ];
            $this->saveSettings($settings);
        }
    }

    private function askForPterodactylPanelCredentialsSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to configure pterodactyl panel credentials? (yes/no)', 'yes') === 'yes') {
            $io->section('Pterodactyl panel credentials');
            $io->text('Please provide pterodactyl panel credentials');

            $settings = [
                SettingEnum::PTERODACTYL_PANEL_URL->value => [
                    'value' => $io->ask(
                        'Pterodactyl Panel URL',
                        $this->settingRepository->getSetting(SettingEnum::PTERODACTYL_PANEL_URL),
                    ),
                ],
                SettingEnum::PTERODACTYL_API_KEY->value => [
                    'value' => $io->ask(
                        'Pterodactyl Panel API Key',
                        $this->settingRepository->getSetting(SettingEnum::PTERODACTYL_API_KEY),
                    ),
                ],
            ];

            $pterodactylConnectionVerification = $this->pterodactylConnectionVerificationService->validateConnection(
                $settings[SettingEnum::PTERODACTYL_PANEL_URL->value]['value'],
                $settings[SettingEnum::PTERODACTYL_API_KEY->value]['value'],
            );

            if (!$pterodactylConnectionVerification->isVerificationSuccessful) {
                $io->error($pterodactylConnectionVerification->message);
                $this->askForPterodactylPanelCredentialsSettings($io);
                return;
            }

            $this->saveSettings($settings);
        }
    }

    private function askForEmailSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to set email settings? (yes/no)', 'yes') === 'yes') {
            $io->section('Email settings');
            $io->text('Please provide email settings');

            $settings = [
                SettingEnum::EMAIL_SMTP_SERVER->value => [
                    'value' => $io->ask(
                        'SMTP Server',
                        $this->settingRepository->getSetting(SettingEnum::EMAIL_SMTP_SERVER),
                    ),
                ],
                SettingEnum::EMAIL_SMTP_PORT->value => [
                    'value' => $io->ask(
                        'SMTP Port',
                        $this->settingRepository->getSetting(SettingEnum::EMAIL_SMTP_PORT),
                    ),
                ],
                SettingEnum::EMAIL_SMTP_USERNAME->value => [
                    'value' => $io->ask(
                        'SMTP Username',
                        $this->settingRepository->getSetting(SettingEnum::EMAIL_SMTP_USERNAME),
                    ),
                ],
                SettingEnum::EMAIL_SMTP_PASSWORD->value => [
                    'value' => $io->ask(
                        'SMTP Password',
                        $this->settingRepository->getSetting(SettingEnum::EMAIL_SMTP_PASSWORD),
                    ),
                ],
                SettingEnum::EMAIL_SMTP_FROM->value => [
                    'value' => $io->ask(
                        'SMTP From (E-mail)',
                        $this->settingRepository->getSetting(SettingEnum::EMAIL_SMTP_FROM),
                    ),
                ],
            ];

            $emailConnectionVerification = $this->emailConnectionVerificationService->validateConnection(
                $settings[SettingEnum::EMAIL_SMTP_USERNAME->value]['value'],
                $settings[SettingEnum::EMAIL_SMTP_PASSWORD->value]['value'],
                $settings[SettingEnum::EMAIL_SMTP_SERVER->value]['value'],
                $settings[SettingEnum::EMAIL_SMTP_PORT->value]['value'],
            );

            if (!$emailConnectionVerification->isVerificationSuccessful) {
                $io->error($emailConnectionVerification->message);
                $this->askForEmailSettings($io);
                return;
            }

            $this->saveSettings($settings);
        }
    }

    private function askForSiteSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to set site settings? (yes/no)', 'yes') === 'yes') {
            $io->section('Site settings');
            $io->text('Please provide site settings');

            $settings = [
                SettingEnum::SITE_URL->value => [
                    'value' => $io->ask(
                        'Site URL',
                        $this->settingRepository->getSetting(SettingEnum::SITE_URL),
                    ),
                ],
                SettingEnum::SITE_TITLE->value => [
                    'value' => $io->ask(
                        'Site Title',
                        $this->settingRepository->getSetting(SettingEnum::SITE_TITLE),
                    ),
                ],
                SettingEnum::LOCALE->value => [
                    'value' => $io->ask(
                        'Locale',
                        $this->settingRepository->getSetting(SettingEnum::LOCALE),
                    ),
                ],
            ];

            $this->saveSettings($settings);
        }
    }

    private function generateAppSecretKeyIfNeeded(SymfonyStyle $io): void
    {
        $appKey = $this->environmentConfigurationHandler->getEnvValue('/^APP_SECRET=(.*)$/m');

        if (empty($appKey)) {
            $appKey = bin2hex(random_bytes(32));
            $pattern = '/^APP_SECRET=(.*)$/m';

            if ($this->environmentConfigurationHandler->getEnvValue($pattern) === '') {
                $pattern = '/^APP_SECRET=/m';
            }

            $this->environmentConfigurationHandler->writeToEnvFile($pattern, "APP_SECRET=$appKey");
            $io->info('App secret key has been generated and saved in .env file');
        }
    }


    private function saveSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $setting = $this->settingRepository->findOneBy(['name' => $key]);
            if (empty($setting)) {
                continue;
            }
            $setting->setValue($value['value']);
            $this->settingRepository->save($setting);
            $this->settingService->saveSettingInCache($key, $value['value']);
            $this->isSaved = true;
        }
    }
}
