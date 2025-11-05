<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\Adapter\Pterodactyl\Application\PterodactylAdapter;
use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use App\Core\DTO\Pterodactyl\Credentials;
use App\Core\Repository\UserRepository;
use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserValidationService
{
    public function __construct(
        private readonly TranslatorInterface   $translator,
        private readonly UserRepository        $userRepository,
        private readonly HttpClientInterface   $httpClient,
    ) {}

    public function validateUserDoesNotExist(
        string $adminEmail,
        string $pterodactylPanelUrl,
        string $pterodactylPanelApiKey,
    ): ConfiguratorVerificationResult
    {
        $localUser = $this->userRepository->findByEmailIncludingDeleted($adminEmail);
        
        if ($localUser !== null) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.user_already_exists_in_local_database'),
            );
        }

        try {
            $adapter = new PterodactylAdapter($this->httpClient);
            $credentials = new Credentials($pterodactylPanelUrl, $pterodactylPanelApiKey);
            $adapter->setCredentials($credentials);

            $users = $adapter->users()->getAllUsersPaginated(1, ['filter[email]' => $adminEmail]);

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
