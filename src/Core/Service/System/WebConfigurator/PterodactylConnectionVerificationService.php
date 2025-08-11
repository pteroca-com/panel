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

            // Check if PteroCA addon is installed
            $addonVersion = $this->checkPterocaAddon($pterodactylApi);
            
            if ($addonVersion) {
                return new ConfiguratorVerificationResult(
                    true,
                    $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_addon_detected', ['version' => $addonVersion]),
                );
            } else {
                throw new Exception('PteroCA addon not detected');
            }
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
