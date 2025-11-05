<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\Adapter\Pterodactyl\Application\PterodactylAdapter;
use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use App\Core\DTO\Pterodactyl\Credentials;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PterodactylConnectionVerificationService
{
    public function __construct(
        private TranslatorInterface   $translator,
        private SettingService        $settingService,
        private HttpClientInterface   $httpClient,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function validateExistingConnection(): ConfiguratorVerificationResult
    {
        $pterodactylPanelUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        $pterodactylApiKey = $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value);

        if (empty($pterodactylPanelUrl) || empty($pterodactylApiKey)) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_not_configured'),
            );
        }

        return $this->validateConnection($pterodactylPanelUrl, $pterodactylApiKey);
    }

    public function validateConnection(
        string $pterodactylPanelUrl,
        string $pterodactylPanelApiKey,
    ): ConfiguratorVerificationResult
    {
        try {
            $adapter = new PterodactylAdapter($this->httpClient);
            $credentials = new Credentials($pterodactylPanelUrl, $pterodactylPanelApiKey);
            $adapter->setCredentials($credentials);

            // Test connection by listing servers
            $adapter->servers()->paginate();

            if (!$this->checkPterocaAddon($adapter)) {
                return new ConfiguratorVerificationResult(
                    false,
                    $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_addon_not_detected'),
                );
            }

            return new ConfiguratorVerificationResult(
                true,
                $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_api_connection_success'),
            );
        } catch (Exception) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_api_error'),
            );
        }
    }

    private function checkPterocaAddon(PterodactylAdapter $adapter): ?string
    {
        try {
            $data = $adapter->pteroca()->getVersion();
            return $data['version'] ?? null;
        } catch (Exception) {
            return null;
        }
    }
}
