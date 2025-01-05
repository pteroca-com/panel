<?php

namespace App\Core\Controller\API;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerService;
use App\Core\Service\Server\ServerWebsocketService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends APIAbstractController
{
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

        if ($server->getUser() !== $this->getUser() || !$this->isGranted(UserRoleEnum::ROLE_ADMIN->name)) {
            throw $this->createAccessDeniedException();
        }

        return $server;
    }
}
