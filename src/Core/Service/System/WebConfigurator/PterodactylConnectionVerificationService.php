<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylConnectionVerificationService
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function validateConnection(
        string $pterodactylPanelUrl,
        string $pterodactylPanelApiKey,
    ): ConfiguratorVerificationResult
    {
        try {
            $pterodactylApi = new PterodactylApi($pterodactylPanelUrl, $pterodactylPanelApiKey);
            $pterodactylApi->servers->paginate();
            
            if (!$this->checkPterocaAddon($pterodactylApi)) {
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

    private function checkPterocaAddon(PterodactylApi $pterodactylApi): ?string
    {
        try {
            $data = $pterodactylApi->http->get('pteroca/version');
            return $data['version'] ?? null;
        } catch (Exception) {
            return null;
        }
    }
}
