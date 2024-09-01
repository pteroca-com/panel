<?php

namespace App\Core\Handler\Installer;

use App\Core\Entity\Setting;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\SettingRepository;
use App\Core\Service\System\EnvironmentConfigurationService;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class SystemSettingConfiguratorHandler
{
    private array $defaultSettings;

    public function __construct(
        private SettingRepository                       $settingRepository,
        private EnvironmentConfigurationService         $environmentConfigurationHandler,
        private DefaultSystemSettingConfiguratorHandler $defaultSystemSettingConfiguratorHandler,
    )
    {
        $this->defaultSettings = $this->defaultSystemSettingConfiguratorHandler::DEFAULT_SETTINGS;
    }

    public function configureSystemSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to configure system settings? (yes/no)', 'yes') === 'yes') {
            $this->generateAppSecretKeyIfNeeded();
            $this->askForSiteSettings($io);
            $this->askForEmailSettings($io);
            $this->askForPterodactylPanelCredentialsSettings($io);
            $this->askForPaymentSettings($io);
            $this->configureDefaultSettings();
            $this->askForConfigureUser($io);
        }
    }

    private function askForConfigureUser(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to create user?', 'yes') === 'yes') {
            $io->section('User configuration');
            $io->text('Please provide user credentials');

            $email = $io->ask('User e-mail', '');
            $password = $io->ask('User password', '');

            exec(sprintf('php bin/console app:create-new-user %s %s', $email, $password));
        }
    }

    private function configureDefaultSettings(): void
    {
        $this->saveSettings($this->defaultSettings, false);
    }

    private function askForPaymentSettings(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to configure payment settings? (yes/no)', 'yes') === 'yes') {
            $io->section('Payment settings');
            $io->text('Please provide payment settings');

            $settings = [
                SettingEnum::STRIPE_SECRET_KEY->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::STRIPE_SECRET_KEY),
                    'value' => $io->ask(
                        'Stripe Secret Key',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::STRIPE_SECRET_KEY)
                    ),
                ],
                SettingEnum::CURRENCY_NAME->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::CURRENCY_NAME),
                    'value' => $io->ask(
                        'Currency',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::CURRENCY_NAME)
                    ),
                ],
                SettingEnum::INTERNAL_CURRENCY_NAME->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::INTERNAL_CURRENCY_NAME),
                    'value' => $io->ask(
                        'Internal Currency Name (balance)',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::INTERNAL_CURRENCY_NAME)
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
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::PTERODACTYL_PANEL_URL),
                    'value' => $io->ask(
                        'Pterodactyl Panel URL',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::PTERODACTYL_PANEL_URL)
                    ),
                ],
                SettingEnum::PTERODACTYL_API_KEY->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::PTERODACTYL_API_KEY),
                    'value' => $io->ask(
                        'Pterodactyl Panel API Key',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::PTERODACTYL_API_KEY)
                    ),
                ],
            ];
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
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::EMAIL_SMTP_SERVER),
                    'value' => $io->ask(
                        'SMTP Server',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::EMAIL_SMTP_SERVER)
                    ),
                ],
                SettingEnum::EMAIL_SMTP_PORT->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::EMAIL_SMTP_PORT),
                    'value' => $io->ask(
                        'SMTP Port',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::EMAIL_SMTP_PORT)
                    ),
                ],
                SettingEnum::EMAIL_SMTP_USERNAME->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::EMAIL_SMTP_USERNAME),
                    'value' => $io->ask(
                        'SMTP Username',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::EMAIL_SMTP_USERNAME)
                    ),
                ],
                SettingEnum::EMAIL_SMTP_PASSWORD->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::EMAIL_SMTP_PASSWORD),
                    'value' => $io->ask(
                        'SMTP Password',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::EMAIL_SMTP_PASSWORD)
                    ),
                ],
                SettingEnum::EMAIL_SMTP_FROM->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::EMAIL_SMTP_FROM),
                    'value' => $io->ask(
                        'SMTP From (E-mail)',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::EMAIL_SMTP_FROM)
                    ),
                ],
            ];
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
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::SITE_URL),
                    'value' => $io->ask(
                        'Site URL',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::SITE_URL)
                    ),
                ],
                SettingEnum::SITE_TITLE->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::SITE_TITLE),
                    'value' => $io->ask(
                        'Site Title',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::SITE_TITLE)
                    ),
                ],
                SettingEnum::LOCALE->value => [
                    'type' => $this->defaultSystemSettingConfiguratorHandler
                        ->getDefaultSettingType(SettingEnum::LOCALE),
                    'value' => $io->ask(
                        'Locale',
                        $this->defaultSystemSettingConfiguratorHandler
                            ->getDefaultSettingValue(SettingEnum::LOCALE)
                    ),
                ],
            ];
            $this->saveSettings($settings);
        }
    }

    private function generateAppSecretKeyIfNeeded(): void
    {
        $appKey = $this->environmentConfigurationHandler->getEnvValue('/^APP_SECRET=(.*)$/m');

        if (empty($appKey)) {
            $appKey = bin2hex(random_bytes(32));
            $pattern = '/^APP_SECRET=(.*)$/m';

            if ($this->environmentConfigurationHandler->getEnvValue($pattern) === '') {
                $pattern = '/^APP_SECRET=/m';
            }

            $this->environmentConfigurationHandler->writeToEnvFile($pattern, "APP_SECRET=$appKey");
        }
    }


    private function saveSettings(array $settings, bool $overwriteIfExists = true): void
    {
        foreach ($settings as $key => $value) {
            $setting = $this->settingRepository->findOneBy(['name' => $key]);
            if ($setting !== null && $overwriteIfExists === false) {
                continue;
            }
            if (empty($setting)) {
                $setting = (new Setting())
                    ->setName($key)
                    ->setType($value['type']);
            }
            $setting->setValue($value['value']);
            $this->settingRepository->save($setting);
        }
    }
}