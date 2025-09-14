<?php

namespace App\Core\Adapter\Pterodactyl;

use App\Core\Contract\Pterodactyl\PterodactylAdapterInterface;
use App\Core\Contract\Pterodactyl\PterodactylNodesInterface;
use App\Core\Contract\Pterodactyl\PterodactylServersInterface;
use App\Core\Contract\Pterodactyl\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\Credentials;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PterodactylAdapter implements PterodactylAdapterInterface
{
    private PterodactylServers $servers;
    private PterodactylUsers $users;
    private Credentials $apiCredentials;
    private bool $isConfigured = false;

    public function __construct(
        public readonly HttpClientInterface $httpClient,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
        $this->setCredentials();
    }

    private function setCredentials(): void
    {
        if ($this->isConfigured) {
            return;
        }

        $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value) ?? '';
        $url = rtrim($pterodactylUrl, '/');
        $apiKey = $this->settingService->getSetting(SettingEnum::PTERODACTYL_API_KEY->value);

        if (empty($url) || empty($apiKey)) {
            throw new \Exception($this->translator->trans('pteroca.system.pterodactyl_error'));
        }

        $this->apiCredentials = new Credentials($url, $apiKey);
        $this->isConfigured = true;
    }

    public function getServers(): PterodactylServersInterface
    {
        if (!isset($this->servers)) {
            $this->servers = new PterodactylServers($this->httpClient, $this->apiCredentials);
        }

        return $this->servers;
    }

    public function getUsers(): PterodactylUsersInterface
    {
        if (!isset($this->users)) {
            $this->users = new PterodactylUsers($this->httpClient, $this->apiCredentials);
        }

        return $this->users;
    }

    public function getNodes(): PterodactylNodesInterface
    {
        return new PterodactylNodes($this->httpClient, $this->apiCredentials);
    }
}
