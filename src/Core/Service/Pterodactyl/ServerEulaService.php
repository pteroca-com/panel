<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Event\Server\ServerEulaAcceptanceFailedEvent;
use App\Core\Event\Server\ServerEulaAcceptanceRequestedEvent;
use App\Core\Event\Server\ServerEulaAcceptedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\SettingService;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ServerEulaService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingService $settingService,
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly ServerLogService $serverLogService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    ) {
    }

    public function acceptServerEula(Server $server, UserInterface $user): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerEulaAcceptanceRequestedEvent(
            $user->getId() ?? 0,
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'EULA acceptance was blocked';
            throw new \RuntimeException($reason);
        }

        try {
            $this->updateEulaFileContent($server, $user);

            $this->pterodactylApplicationService
                ->getClientApi($user)
                ->servers()
                ->sendPowerSignal(
                    $server->getPterodactylServerIdentifier(),
                    'restart'
                );

            $this->serverLogService->logServerAction($user, $server, ServerLogActionEnum::ACCEPT_EULA);

            $acceptedEvent = new ServerEulaAcceptedEvent(
                $user->getId() ?? 0,
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $context
            );
            $this->eventDispatcher->dispatch($acceptedEvent);

            return ['success' => true];
        } catch (Exception $e) {
            $failedEvent = new ServerEulaAcceptanceFailedEvent(
                $user->getId() ?? 0,
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

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
