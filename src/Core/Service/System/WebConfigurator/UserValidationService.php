<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class UserValidationService
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function validateUserDoesNotExist(
        string $adminEmail,
        string $pterodactylPanelUrl,
        string $pterodactylPanelApiKey,
    ): ConfiguratorVerificationResult
    {
        try {
            $pterodactylApi = new PterodactylApi($pterodactylPanelUrl, $pterodactylPanelApiKey);
            $users = $pterodactylApi->users->paginate(1, ['filter[email]' => $adminEmail]);
        
            if (!empty($users->toArray())) {
                return new ConfiguratorVerificationResult(
                    false,
                    $this->translator->trans('pteroca.first_configuration.messages.user_already_exists_in_pterodactyl'),
                );
            }

            return new ConfiguratorVerificationResult(
                true,
                $this->translator->trans('pteroca.first_configuration.messages.user_validation_success'),
            );
        } catch (Exception) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.pterodactyl_api_error'),
            );
        }
    }
}
