<?php

namespace App\Core\Controller\API;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
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
    ) {}

    #[Route('/panel/api/server/{id}/details', name: 'server_details', methods: ['GET'])]
    public function serverDetails(
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id);
        $serverDetails = $this->serverService->getServerDetails($server);

        return new JsonResponse($serverDetails);
    }

    #[Route('/panel/api/server/{id}/websocket', name: 'server_websocket_token', methods: ['GET'])]
    public function serverWebsocketToken(
        int $id,
        ServerWebsocketService $serverWebsocketService,
    ): JsonResponse
    {
        $server = $this->getServer($id);
        $websocket = $serverWebsocketService->getWebsocketToken($server);

        return new JsonResponse([
            'token' => $websocket?->getToken(),
            'socket' => $websocket?->getSocket(),
        ]);
    }

    private function getServer(int $id): Server
    {
        $server = $this->serverRepository->find($id);
        if (empty($server)) {
            throw $this->createNotFoundException();
        }

        if ($server->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $server;
    }
}
