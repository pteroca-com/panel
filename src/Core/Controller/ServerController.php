<?php

namespace App\Core\Controller;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerDataService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\Server\ServerWebsocketService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends AbstractController
{
    #[Route('/servers', name: 'servers')]
    public function servers(
        ServerRepository $serverRepository,
    ): Response
    {
        $this->checkPermission();
        $imagePath = $this->getParameter('products_base_path') . '/';

        $servers = array_map(function ($server) use ($imagePath) {
            if (!empty($server->getProduct()->getImagePath())) {
                $server->imagePath = $imagePath . $server->getProduct()->getImagePath();
            }
            return $server;
        }, $serverRepository->findBy(['user' => $this->getUser()]));

        return $this->render('panel/servers/servers.html.twig', [
            'servers' => $servers,
        ]);
    }

    #[Route('/server', name: 'server', requirements: ['id' => '\d+'])]
    public function server(
        Request $request,
        ServerRepository $serverRepository,
        ServerWebsocketService $serverWebsocketService,
        ServerService $serverService,
        ServerDataService $serverDataService,
    ): Response
    {
        $this->checkPermission();

        $serverId = $request->get('id');
        if (empty($serverId) || !is_numeric($serverId)) {
            throw $this->createNotFoundException(); // TODO: Add message
        }

        /** @var Server $server */
        $server = $serverRepository->find($serverId);
        if (empty($server)) {
            throw $this->createNotFoundException(); // TODO: Add message
        }

        $isAdminView = $this->isGranted(UserRoleEnum::ROLE_ADMIN->name) && $server->getUser() !== $this->getUser();
        if ($server->getUser() !== $this->getUser() && !$isAdminView) {
            throw $this->createAccessDeniedException(); // TODO: Add message
        }

        $serverData = $serverDataService->getServerData($server);

        return $this->render('panel/server/server.html.twig', [
            'server' => $server,
            'serverDetails' => $serverService->getServerDetails($server),
            'serverData' => $serverData,
            'websocket' => $serverWebsocketService->establishWebsocketConnection($server),
            'isAdminView' => $isAdminView,
        ]);
    }
}
