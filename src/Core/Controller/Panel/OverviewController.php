<?php

namespace App\Core\Controller\Panel;

use App\Core\Controller\AbstractController;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Enum\WidgetContext;
use App\Core\Event\Admin\AdminOverviewAccessedEvent;
use App\Core\Event\Admin\AdminOverviewDataLoadedEvent;
use App\Core\Event\Widget\WidgetsCollectedEvent;
use App\Core\Service\Statistics\AdminStatisticsService;
use App\Core\Service\System\SystemInformationService;
use App\Core\Service\Widget\WidgetRegistry;
use App\Core\Widget\Admin\RecentPaymentsWidget;
use App\Core\Widget\Admin\RecentUsersWidget;
use App\Core\Widget\Admin\SystemInfoWidget;
use App\Core\Widget\Admin\SystemStatsWidget;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverviewController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SystemStatsWidget $systemStatsWidget,
        private readonly RecentPaymentsWidget $recentPaymentsWidget,
        private readonly RecentUsersWidget $recentUsersWidget,
        private readonly SystemInfoWidget $systemInfoWidget,
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

        // === Widget Registry System ===
        $widgetRegistry = new WidgetRegistry();

        // Register builtin admin widgets
        $this->registerAdminWidgets($widgetRegistry);

        // Dispatch event for plugins to register custom widgets
        $contextData = [
            'user' => $user,
            'statistics' => $statistics,
            'systemInformation' => $systemInformation,
        ];

        $widgetEvent = new WidgetsCollectedEvent(
            $widgetRegistry,
            WidgetContext::ADMIN_OVERVIEW,
            $contextData
        );
        $this->dispatchEvent($widgetEvent);
        // === End Widget Registry System ===

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
            'widgetRegistry' => $widgetRegistry,
            'widgetContext' => WidgetContext::ADMIN_OVERVIEW,
            'contextData' => $contextData,
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

    /**
     * Register builtin (core) admin overview widgets.
     *
     * @param WidgetRegistry $registry
     * @return void
     */
    private function registerAdminWidgets(WidgetRegistry $registry): void
    {
        $registry->registerWidget($this->systemStatsWidget);
        $registry->registerWidget($this->recentPaymentsWidget);
        $registry->registerWidget($this->recentUsersWidget);
        $registry->registerWidget($this->systemInfoWidget);
    }
}