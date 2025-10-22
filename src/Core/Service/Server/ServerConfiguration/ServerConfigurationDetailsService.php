<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Event\Server\Configuration\ServerDetailsUpdateRequestedEvent;
use App\Core\Event\Server\Configuration\ServerDetailsUpdatedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    ) {
        parent::__construct($this->pterodactylApplicationService);
    }

    public function updateServerDetails(Server $server, UserInterface $user, string $serverName, ?string $serverDescription): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $pterodactylClientApi = $this->pterodactylApplicationService
            ->getClientApi($user);
        $pterodactylServer = $pterodactylClientApi->servers()
            ->getServer($server->getPterodactylServerIdentifier());

        $oldServerName = $pterodactylServer->get('name') ?? '';
        $oldServerDescription = $pterodactylServer->get('description');

        $description = $serverDescription ?? $oldServerDescription;

        $requestedEvent = new ServerDetailsUpdateRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $serverName,
            $description,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Server details update was blocked';
            throw new \Exception($reason);
        }

        $pterodactylClientApi
            ->servers()
            ->updateServerName(
                $server->getPterodactylServerIdentifier(),
                $serverName,
                $description
            );

        $updatedEvent = new ServerDetailsUpdatedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $serverName,
            $description,
            $oldServerName,
            $oldServerDescription,
            $context
        );
        $this->eventDispatcher->dispatch($updatedEvent);
    }
}
