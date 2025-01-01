<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PterodactylClientApiKeyService
{
    private const PTERODACTYL_CLIENT_API_KEY_DESCRIPTION = 'PteroCA Client API Key';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingService $settingService,
    )
    {
    }

    public function createClientApiKey(User $user): string
    {
        $endpointUrl = sprintf(
            '%s/api/application/users/%d/api-keys',
            $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value),
            $user->getPterodactylUserId()
        );

        $authorizationHeader = sprintf(
            'Bearer %s',
            $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value)
        );

        $createdApiKey = $this->httpClient->request('POST', $endpointUrl, [
            'json' => [
                'description' => self::PTERODACTYL_CLIENT_API_KEY_DESCRIPTION,
            ],
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
            ],
        ]);

        $createdApiKey = json_decode(
            $createdApiKey->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return sprintf(
            '%s%s',
            $createdApiKey['attributes']['identifier'],
            $createdApiKey['meta']['secret_token'],
        );
    }
}
