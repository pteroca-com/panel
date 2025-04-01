<?php

namespace App\Core\Controller;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Server\ServerDataService;
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

        $servers = array_map(function (Server $server) use ($imagePath) {
            if (!empty($server->getServerProduct()->getOriginalProduct()?->getImagePath())) {
                $server->imagePath = $imagePath . $server->getServerProduct()->getOriginalProduct()?->getImagePath();
            }
            return $server;
        }, $serverRepository->findBy(['user' => $this->getUser()]));

        return $this->render('panel/servers/servers.html.twig', [
            'servers' => $servers,
        ]);
    }

    #[Route('/server', name: 'server')]
    public function server(
        Request $request,
        ServerRepository $serverRepository,
        ServerDataService $serverDataService,
        ServerLogService $serverLogService,
    ): Response
    {
        $this->checkPermission();

        $serverId = $request->get('id');
        $currentPage = $request->get('page', 1);
        if (empty($serverId)) {
            throw $this->createNotFoundException();
        }

        /** @var ?Server $server */
        $server = current($serverRepository->findBy(['pterodactylServerIdentifier' => $serverId]));
        if (empty($server)) {
            throw $this->createNotFoundException();
        }

        $isAdminView = $this->isGranted(UserRoleEnum::ROLE_ADMIN->name);
        if ($server->getUser() !== $this->getUser() && !$isAdminView) {
            throw $this->createAccessDeniedException();
        }

        $serverData = $serverDataService->getServerData($server);

        return $this->render('panel/server/server.html.twig', [
            'server' => $server,
            'serverData' => $serverData,
            'isAdminView' => $isAdminView,
            'serverLogs' => $serverLogService->getServerLogsWithPagination($server, $currentPage),
        ]);
    }
}
