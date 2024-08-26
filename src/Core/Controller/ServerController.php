<?php

namespace App\Core\Controller;

use App\Core\Enum\SettingEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\SettingService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends AbstractController
{
    #[Route('/servers', name: 'servers')]
    public function servers(
        ServerRepository $serverRepository,
        SettingService $settingService,
    ): Response
    {
        $this->checkPermission();
        $pterodactylPanelUrl = $settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        $imagePath = $this->getParameter('products_base_path') . '/';

        $servers = array_map(function ($server) use ($imagePath) {
            if (!empty($server->getProduct()->getImagePath())) {
                $server->imagePath = $imagePath . $server->getProduct()->getImagePath();
            }
            return $server;
        }, $serverRepository->findBy(['user' => $this->getUser()]));

        return $this->render('panel/servers/servers.html.twig', [
            'servers' => $servers,
            'pterodactylPanelUrl' => $pterodactylPanelUrl,
        ]);
    }
}
