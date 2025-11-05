<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Event\Server\Configuration\ServerReinstallInitiatedEvent;
use App\Core\Event\Server\Configuration\ServerReinstallRequestedEvent;
use App\Core\Event\Server\Configuration\ServerReinstalledEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerReinstallationService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService     $pterodactylApplicationService,
        private readonly ServerConfigurationStartupService $serverConfigurationStartupService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    )
    {
        parent::__construct($this->pterodactylApplicationService);
    }

    /**
     * @throws Exception
     */
    public function reinstallServer(Server $server, UserInterface $user, ?int $selectedEgg): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $serverDetails = $this->getServerDetails($server, ['egg']);
        $currentEgg = $serverDetails['egg'];
        $eggChanged = false;

        $requestedEvent = new ServerReinstallRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $selectedEgg,
            $currentEgg,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Server reinstall was blocked';
            throw new Exception($reason);
        }

        if ($selectedEgg && $selectedEgg !== $currentEgg) {
            $this->validateEgg($server, $selectedEgg);
            $startupPayload = $this->serverConfigurationStartupService
                ->getStartupPayload('egg', $selectedEgg, $serverDetails);

            $this->serverConfigurationStartupService->updateServerStartup($server, $startupPayload);
            $eggChanged = true;
        }

        $initiatedEvent = new ServerReinstallInitiatedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $selectedEgg,
            $currentEgg,
            $eggChanged,
            $context
        );
        $this->eventDispatcher->dispatch($initiatedEvent);

        $this->pterodactylApplicationService
            ->getClientApi($user)
            ->servers()
            ->reinstallServer($server->getPterodactylServerIdentifier());

        $reinstalledEvent = new ServerReinstalledEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $selectedEgg,
            $currentEgg,
            $eggChanged,
            $context
        );
        $this->eventDispatcher->dispatch($reinstalledEvent);
    }

    /**
     * @throws Exception
     */
    private function validateEgg(Server $server, int $selectedEgg): void
    {
        if (!in_array($selectedEgg, $server->getServerProduct()->getEggs())) {
            throw new Exception('Invalid egg');
        }
    }
}
