<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Pterodactyl\ServerEulaService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\Server\ServerWebsocketService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerService $serverService,
        private readonly ServerRepository $serverRepository,
        private readonly PterodactylService $pterodactylService,
        private readonly ServerEulaService $serverEulaService,
    ) {}

    #[Route('/panel/api/server/{id}/details', name: 'server_details', methods: ['GET'])]
    public function serverDetails(
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::WEBSOCKET_CONNECT);
        $serverDetails = $this->serverService
            ->getServerStateByClient($this->getUser(), $server)
            ?->toArray();
        unset($serverDetails['egg']);

        return new JsonResponse($serverDetails);
    }

    #[Route('/panel/api/server/{id}/websocket', name: 'server_websocket_token', methods: ['GET'])]
    public function serverWebsocketToken(
        int $id,
        ServerWebsocketService $serverWebsocketService,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::WEBSOCKET_CONNECT);
        $websocket = $serverWebsocketService->getWebsocketToken($server, $this->getUser());

        return new JsonResponse($websocket->toArray());
    }

    #[Route('/panel/api/server/{id}/accept-eula', name: 'panel_server_accept_eula', methods: ['POST'])]
    public function acceptEula(int $id): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::CONTROL_START);
        
        try {
            $result = $this->serverEulaService->acceptServerEula($server, $this->getUser());
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
