<?php

namespace App\Core\Controller;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Event\Server\ServersListAccessedEvent;
use App\Core\Event\Server\ServersListDataLoadedEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerDataService;
use App\Core\Service\Server\ServerService;
use App\Core\Trait\EventContextTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends AbstractController
{
    use EventContextTrait;

    #[Route('/servers', name: 'servers')]
    public function servers(
        Request $request,
        ServerService $serverService,
    ): Response
    {
        $this->checkPermission();

        $context = $this->buildMinimalEventContext($request);
        $this->dispatchEvent(new ServersListAccessedEvent(
            $this->getUser()->getId(),
            $context
        ));
        
        $imagePath = $this->getParameter('products_base_path') . '/';
        $servers = array_map(function (Server $server) use ($imagePath) {
            if (!empty($server->getServerProduct()->getOriginalProduct()?->getImagePath())) {
                $server->imagePath = $imagePath . $server->getServerProduct()->getOriginalProduct()?->getImagePath();
            }
            return $server;
        }, $serverService->getServersWithAccess($this->getUser()));

        $this->dispatchEvent(new ServersListDataLoadedEvent(
            $this->getUser()->getId(),
            $servers,
            count($servers),
            $context
        ));
        
        $viewData = [
            'servers' => $servers,
        ];

        $viewEvent = $this->dispatchEvent(new ViewDataEvent(
            'servers_list',
            $viewData,
            $this->getUser(),
            $context
        ));

        return $this->render('panel/servers/servers.html.twig', $viewEvent->getViewData());
    }

    #[Route('/server', name: 'server')]
    public function server(
        Request $request,
        ServerRepository $serverRepository,
        ServerDataService $serverDataService,
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
        if (empty($server) || !empty($server->getDeletedAt())) {
            throw $this->createNotFoundException();
        }

        $serverData = $serverDataService->getServerData($server, $this->getUser(), $currentPage);
        $isAdminView = $this->isGranted(UserRoleEnum::ROLE_ADMIN->name);
        $isOwner = $server->getUser() === $this->getUser();
        if (empty($serverData->serverPermissions?->toArray()) && !$isAdminView) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('panel/server/server.html.twig', [
            'server' => $server,
            'serverData' => $serverData,
            'isAdminView' => $isAdminView,
            'isOwner' => $isOwner,
        ]);
    }
}
