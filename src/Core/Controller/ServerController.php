<?php

namespace App\Core\Controller;

use App\Core\Entity\Server;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerNestService;
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
        PterodactylService $pterodactylService,
        PterodactylClientService $pterodactylClientService,
        ServerNestService $serverNestService,
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

        if ($server->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException(); // TODO: Add message
        }

        // @TODO: move to service
        $pterodactylServer = $pterodactylService->getApi()->servers->get($server->getPterodactylServerId(), [
            'include' => ['variables', 'egg'],
        ]);
        $pterodactylClientApi = $pterodactylClientService
            ->getApi($server->getUser());
        $pterodactylClientServer = $pterodactylClientApi
            ->servers
            ->get($server->getPterodactylServerIdentifier());
        $pterodactylClientAccount = $pterodactylClientApi
            ->account
            ->details();
        $productEggsConfiguration = $server->getProduct()->getEggsConfiguration();

        try {
            $productEggsConfiguration = json_decode($productEggsConfiguration, true, 512, JSON_THROW_ON_ERROR);
            $productEggConfiguration = $productEggsConfiguration[$pterodactylServer->get('egg')] ?? [];
        } catch (\Exception $e) {
            $productEggConfiguration = [];
        }

        if ($server->getProduct()->getAllowChangeEgg()) {
            $availableNestEggs = $serverNestService->getServerAvailableEggs($server);
        }

        $dockerImages = $pterodactylServer->get('relationships')['egg']->get('docker_images');

        return $this->render('panel/server/server.html.twig', [
            'server' => $server,
            'serverDetails' => $serverService->getServerDetails($server),
            'pterodactylServer' => $pterodactylServer,
            'pterodactylClientServer' => $pterodactylClientServer,
            'pterodactylClientAccount' => $pterodactylClientAccount,
            'websocket' => $serverWebsocketService->establishWebsocketConnection($server),
            'productEggConfiguration' => $productEggConfiguration,
            'dockerImages' => $dockerImages,
            'availableNestEggs' => $availableNestEggs ?? null,
        ]);
    }
}
