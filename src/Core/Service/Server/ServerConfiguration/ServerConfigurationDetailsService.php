<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Event\Server\Configuration\ServerDetailsUpdateRequestedEvent;
use App\Core\Event\Server\Configuration\ServerDetailsUpdatedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

class ServerConfigurationDetailsService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
        private readonly ServerRepository $serverRepository,
    ) {
        parent::__construct($this->pterodactylApplicationService);
    }

    /**
     * @throws Exception
     */
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
        $preparedServerName = trim(substr($serverName, 0, 255));
        $preparedServerDescription = trim(substr($description ?? '', 0, 255));

        $requestedEvent = new ServerDetailsUpdateRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $preparedServerName,
            $preparedServerDescription,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Server details update was blocked';
            throw new Exception($reason);
        }

        $pterodactylClientApi
            ->servers()
            ->updateServerName(
                $server->getPterodactylServerIdentifier(),
                $preparedServerName,
                $preparedServerDescription
            );

        $updatedEvent = new ServerDetailsUpdatedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $preparedServerName,
            $preparedServerDescription,
            $oldServerName,
            $oldServerDescription,
            $context
        );
        $this->eventDispatcher->dispatch($updatedEvent);
    }

    public function updateServerEntityName(Server $server, string $serverName): void
    {
        $preparedServerName = trim(substr($serverName, 0, 255));
        $server->setName($preparedServerName);
        $this->serverRepository->save($server);
    }
}
