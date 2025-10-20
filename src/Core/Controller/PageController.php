<?php

namespace App\Core\Controller;

use App\Core\Enum\SettingEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Page\PageAccessedEvent;
use App\Core\Event\Page\PageDataLoadedEvent;
use App\Core\Service\SettingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/terms-of-service', name: 'terms_of_service')]
    public function index(
        SettingService $settingService,
        Request $request,
    ): Response {
        $pageType = 'terms_of_service';

        $this->dispatchDataEvent(
            PageAccessedEvent::class,
            $request,
            [$pageType]
        );

        $pageContent = $settingService->getSetting(SettingEnum::TERMS_OF_SERVICE->value);
        $hasContent = !empty($pageContent);
        $contentLength = $hasContent ? strlen($pageContent) : 0;

        $this->dispatchDataEvent(
            PageDataLoadedEvent::class,
            $request,
            [$pageType, $hasContent, $contentLength]
        );

        if (!$hasContent) {
            throw new NotFoundHttpException();
        }

        $viewData = [
            'pageContent' => $pageContent,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::TERMS_OF_SERVICE,
            'panel/page/default.html.twig',
            $viewData,
            $request
        );
    }
}
