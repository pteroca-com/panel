<?php

namespace App\Core\Controller;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/terms-of-service', name: 'terms_of_service')]
    public function index(
        SettingService $settingService,
    ): Response {
        $pageContent = $settingService->getSetting(SettingEnum::TERMS_OF_SERVICE->value);
        if (empty($pageContent)) {
            throw new NotFoundHttpException();
        }
        return $this->render('panel/page/default.html.twig', [
            'pageContent' => $pageContent,
        ]);
    }
}
