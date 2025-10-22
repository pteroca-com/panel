<?php

namespace App\Core\Controller;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Server\ServerManagementDataLoadedEvent;
use App\Core\Event\Server\ServerManagementPageAccessedEvent;
use App\Core\Event\Server\ServersListAccessedEvent;
use App\Core\Event\Server\ServersListDataLoadedEvent;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerDataService;
use App\Core\Service\Server\ServerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends AbstractController
{

    #[Route('/servers', name: 'servers')]
    public function servers(
        Request $request,
        ServerService $serverService,
    ): Response
    {
        $this->checkPermission();

        $this->dispatchSimpleEvent(ServersListAccessedEvent::class, $request);

        $imagePath = $this->getParameter('products_base_path') . '/';
        $servers = array_map(function (Server $server) use ($imagePath) {
            if (!empty($server->getServerProduct()->getOriginalProduct()?->getImagePath())) {
                $server->imagePath = $imagePath . $server->getServerProduct()->getOriginalProduct()?->getImagePath();
            }
            return $server;
        }, $serverService->getServersWithAccess($this->getUser()));

        $this->dispatchDataEvent(
            ServersListDataLoadedEvent::class,
            $request,
            [$servers, count($servers)]
        );

        $viewData = [
            'servers' => $servers,
        ];

        return $this->renderWithEvent(ViewNameEnum::SERVERS_LIST, 'panel/servers/servers.html.twig', $viewData, $request);
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

        $this->dispatchDataEvent(
            ServerManagementPageAccessedEvent::class,
            $request,
            [
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $server->getServerProduct()->getName(),
                $server->getUser() === $this->getUser(), // isOwner
                $this->isGranted(UserRoleEnum::ROLE_ADMIN->name), // isAdminView
            ]
        );

        $serverData = $serverDataService->getServerData($server, $this->getUser(), $currentPage);
        $isAdminView = $this->isGranted(UserRoleEnum::ROLE_ADMIN->name);
        $isOwner = $server->getUser() === $this->getUser();

        $loadedDataSections = [];
        if (!empty($serverData->pterodactylServer)) $loadedDataSections[] = 'pterodactyl_server';
        if (!empty($serverData->allocatedPorts)) $loadedDataSections[] = 'allocations';
        if (!empty($serverData->serverBackups)) $loadedDataSections[] = 'backups';
        if (!empty($serverData->subusers)) $loadedDataSections[] = 'subusers';
        if (!empty($serverData->activityLogs)) $loadedDataSections[] = 'activity_logs';
        if (!empty($serverData->serverSchedules)) $loadedDataSections[] = 'schedules';
        if (!empty($serverData->serverDetails)) $loadedDataSections[] = 'server_details';
        if (!empty($serverData->serverVariables)) $loadedDataSections[] = 'server_variables';
        if (!empty($serverData->dockerImages)) $loadedDataSections[] = 'docker_images';
        if (!empty($serverData->availableNestEggs)) $loadedDataSections[] = 'available_nest_eggs';

        $this->dispatchDataEvent(
            ServerManagementDataLoadedEvent::class,
            $request,
            [
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $serverData->isInstalling ?? false,
                $serverData->isSuspended ?? false,
                !empty($serverData->serverPermissions?->toArray()),
                $loadedDataSections,
            ]
        );

        if (empty($serverData->serverPermissions?->toArray()) && !$isAdminView) {
            throw $this->createAccessDeniedException();
        }

        return $this->renderWithEvent(
            ViewNameEnum::SERVER_MANAGEMENT,
            'panel/server/server.html.twig',
            [
                'server' => $server,
                'serverData' => $serverData,
                'isAdminView' => $isAdminView,
                'isOwner' => $isOwner,
            ],
            $request
        );
    }
}
