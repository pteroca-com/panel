<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Event\Server\ServerEulaAcceptanceFailedEvent;
use App\Core\Event\Server\ServerEulaAcceptanceRequestedEvent;
use App\Core\Event\Server\ServerEulaAcceptedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Logs\ServerLogService;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class ServerEulaService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private ServerLogService              $serverLogService,
        private EventDispatcherInterface      $eventDispatcher,
        private RequestStack                  $requestStack,
        private EventContextService           $eventContextService,
    ) {
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
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
            throw new RuntimeException($reason);
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

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    private function updateEulaFileContent(Server $server, UserInterface $user): void
    {
        $eulaContent = sprintf(
            "# EULA accepted via PteroCA Panel on %s\neula=true\n",
            date('Y-m-d H:i:s')
        );

        $this->pterodactylApplicationService
            ->getClientApi($user)
            ->files()
            ->writeFile(
                $server->getPterodactylServerIdentifier(),
                '/eula.txt',
                $eulaContent
            );
    }
}
