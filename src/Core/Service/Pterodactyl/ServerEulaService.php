<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\SettingService;
use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ServerEulaService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingService $settingService,
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
    ) {
    }

    public function acceptServerEula(Server $server, UserInterface $user): array
    {
        try {
            $this->updateEulaFileContent($server, $user);

            $api = $this->pterodactylClientService->getApi($user);
            $api->servers->power($server->getPterodactylServerIdentifier(), 'restart');

            $this->serverLogService->logServerAction($user, $server, ServerLogActionEnum::ACCEPT_EULA);

            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception('Failed to accept EULA: ' . $e->getMessage());
        }
    }

    private function updateEulaFileContent(Server $server, UserInterface $user): void
    {
        $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        $eulaContent = sprintf(
            "# EULA accepted via PteroCA Panel on %s\neula=true\n",
            date('Y-m-d H:i:s')
        );

        $url = sprintf(
            "%s/api/client/servers/%s/files/write?%s",
            rtrim($pterodactylUrl, '/'),
            $server->getPterodactylServerIdentifier(),
            http_build_query(['file' => '/eula.txt'])
        );

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $user->getPterodactylUserApiKey(),
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
                'Content-Type' => 'text/plain',
            ],
            'body' => $eulaContent,
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception('Failed to write EULA file');
        }
    }
}
