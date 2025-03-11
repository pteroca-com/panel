<?php

namespace App\Core\Service\System\WebConfigurator;

use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylConnectionVerificationService extends AbstractVerificationService
{
    protected const REQUIRED_FIELDS = [
        'pterodactyl_panel_url',
        'pterodactyl_panel_api_key',
    ];

    public function validateConnection(array $data): bool
    {
        if (!$this->validateRequiredFields($data)) {
            return false;
        }

        try {
            $pterodactylApi = new PterodactylApi($data['pterodactyl_panel_url'], $data['pterodactyl_panel_api_key']);
            $pterodactylApi->servers->paginate();

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
