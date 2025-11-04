<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Event\Server\ServerDetailsLoadedEvent;
use App\Core\Event\Server\ServerDetailsRequestedEvent;
use App\Core\Event\Server\ServerWebsocketTokenGeneratedEvent;
use App\Core\Event\Server\ServerWebsocketTokenRequestedEvent;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use App\Core\Service\Pterodactyl\ServerEulaService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\Server\ServerWebsocketService;
use App\Core\Trait\InternalServerApiTrait;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerService $serverService,
        private readonly ServerRepository $serverRepository,
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly ServerEulaService $serverEulaService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    ) {}

    #[Route('/panel/api/server/{id}/details', name: 'server_details', methods: ['GET'])]
    public function serverDetails(
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::WEBSOCKET_CONNECT);
        $user = $this->getUser();

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerDetailsRequestedEvent(
            $this->getUserId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        $serverDetailsDTO = $this->serverService->getServerStateByClient($user, $server);
        $serverDetails = $serverDetailsDTO?->toArray();
        unset($serverDetails['egg']);
        $loadedEvent = new ServerDetailsLoadedEvent(
            $this->getUserId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $serverDetailsDTO?->state?->value,
            $server->getIsSuspended(),
            $context
        );
        $this->eventDispatcher->dispatch($loadedEvent);

        return new JsonResponse($serverDetails);
    }

    #[Route('/panel/api/server/{id}/websocket', name: 'server_websocket_token', methods: ['GET'])]
    public function serverWebsocketToken(
        int $id,
        ServerWebsocketService $serverWebsocketService,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::WEBSOCKET_CONNECT);
        $user = $this->getUser();

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerWebsocketTokenRequestedEvent(
            $this->getUserId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        $websocket = $serverWebsocketService->getWebsocketToken($server, $user);

        $generatedEvent = new ServerWebsocketTokenGeneratedEvent(
            $this->getUserId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $context
        );
        $this->eventDispatcher->dispatch($generatedEvent);

        return new JsonResponse($websocket->toArray());
    }

    #[Route('/panel/api/server/{id}/accept-eula', name: 'panel_server_accept_eula', methods: ['POST'])]
    public function acceptEula(int $id): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::CONTROL_START);
        
        try {
            $result = $this->serverEulaService->acceptServerEula($server, $this->getUser());
            return new JsonResponse($result);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
