<?php

namespace App\Core\Controller\Panel;

use App\Core\Controller\AbstractController;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Admin\AdminOverviewAccessedEvent;
use App\Core\Event\Admin\AdminOverviewDataLoadedEvent;
use App\Core\Service\Statistics\AdminStatisticsService;
use App\Core\Service\System\SystemInformationService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverviewController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    #[Route('/admin/overview', name: 'admin_overview')]
    public function index(
        SystemInformationService $systemInformationService,
        AdminStatisticsService $adminStatisticsService,
    ): Response
    {
        $this->checkPermission(UserRoleEnum::ROLE_ADMIN->name);

        $user = $this->getUser();
        $request = $this->requestStack->getCurrentRequest();

        $this->dispatchDataEvent(
            AdminOverviewAccessedEvent::class,
            $request,
            [$user->getRoles()]
        );

        $systemInformation = $systemInformationService->getSystemInformation();
        $statistics = $adminStatisticsService->getAdminStatistics();

        $this->dispatchDataEvent(
            AdminOverviewDataLoadedEvent::class,
            $request,
            [
                $statistics['activeServers'],
                $statistics['usersRegisteredLastMonth'],
                $statistics['paymentsCreatedLastMonth'],
                $systemInformation['pterodactyl']['status'],
            ]
        );

        $viewData = [
            'systemInformation' => $systemInformation,
            'statistics' => $statistics,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::ADMIN_OVERVIEW,
            'panel/admin/overview.html.twig',
            $viewData,
            $request
        );
    }
}