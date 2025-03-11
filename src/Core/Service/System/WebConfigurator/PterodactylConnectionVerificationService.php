<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylConnectionVerificationService extends AbstractVerificationService
{
    protected const REQUIRED_FIELDS = [
        'pterodactyl_panel_url',
        'pterodactyl_panel_api_key',
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function validateConnection(array $data): ConfiguratorVerificationResult
    {
        if (!$this->validateRequiredFields($data)) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.errors.missing_fields'),
            );
        }

        try {
            $pterodactylApi = new PterodactylApi($data['pterodactyl_panel_url'], $data['pterodactyl_panel_api_key']);
            $pterodactylApi->servers->paginate();

            return new ConfiguratorVerificationResult(true);
        } catch (Exception) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.errors.pterodactyl_api_error'),
            );
        }
    }
}
