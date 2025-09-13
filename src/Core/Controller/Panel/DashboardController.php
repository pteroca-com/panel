<?php

namespace App\Core\Controller\Panel;

use App\Core\Controller\Panel\Setting\EmailSettingCrudController;
use App\Core\Controller\Panel\Setting\GeneralSettingCrudController;
use App\Core\Controller\Panel\Setting\PaymentSettingCrudController;
use App\Core\Controller\Panel\Setting\PterodactylSettingCrudController;
use App\Core\Controller\Panel\Setting\SecuritySettingCrudController;
use App\Core\Controller\Panel\Setting\ThemeSettingCrudController;
use App\Core\Entity\Category;
use App\Core\Entity\EmailLog;
use App\Core\Entity\Log;
use App\Core\Entity\Panel\UserAccount;
use App\Core\Entity\Panel\UserPayment;
use App\Core\Entity\Payment;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\ServerLog;
use App\Core\Entity\User;
use App\Core\Entity\Voucher;
use App\Core\Entity\VoucherUsage;
use App\Core\Enum\SettingContextEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use App\Core\Service\System\SystemVersionService;
use App\Core\Service\Template\TemplateManager;
use App\Core\Trait\GetUserTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ColorScheme;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController
{
    use GetUserTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
        private readonly ServerRepository $serverRepository,
        private readonly LogService $logService,
        private readonly SystemVersionService $systemVersionService,
        private readonly TemplateManager $templateManager,
        private readonly ServerService $serverService,
    ) {}

    #[Route('/panel', name: 'panel')]
    public function index(): Response
    {
        $user = $this->getUser();
        $pterodactylPanelUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);

        return $this->render('panel/dashboard/dashboard.html.twig', [
            'servers' => $this->serverService->getServersWithAccess($user),
            'user' => $user,
            'logs' => $this->logService->getLogsByUser($user, 5),
            'motdEnabled' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_ENABLED->value),
            'motdTitle' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_TITLE->value),
            'motdMessage' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_MESSAGE->value),
            'pterodactylPanelUrl' => $pterodactylPanelUrl,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        $title = $this->settingService->getSetting(SettingEnum::SITE_TITLE->value);
        $logoUrl = $this->settingService->getSetting(SettingEnum::LOGO->value);
        if (!empty($logoUrl)) {
            $logoUrl = sprintf('/uploads/settings/%s', $logoUrl);
        } else {
            $logoUrl = '/assets/img/logo/logo.png';
        }
        $logo = sprintf('<img src="%s" alt="%s" style="max-width: 90%%;">', $logoUrl, $title);

        $currentTemplateOptions = $this->templateManager->getCurrentTemplateOptions();
        $disableDarkMode = !$currentTemplateOptions->isSupportDarkMode()
            || $this->settingService->getSetting(SettingEnum::THEME_DISABLE_DARK_MODE->value);
        $defaultMode = $disableDarkMode
            ? ColorScheme::LIGHT
            : $this->settingService->getSetting(SettingEnum::THEME_DEFAULT_MODE->value);

        return Dashboard::new()
            ->setTitle($logo)
            ->setDefaultColorScheme($defaultMode)
            ->disableDarkMode($disableDarkMode)
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section($this->translator->trans('pteroca.crud.menu.menu'));
        yield MenuItem::linkToDashboard($this->translator->trans('pteroca.crud.menu.dashboard'), 'fa fa-home');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.my_servers'), 'fa fa-server', 'servers');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.shop'), 'fa fa-shopping-cart', 'store');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.wallet'), 'fa fa-wallet', 'recharge_balance');
        yield MenuItem::subMenu($this->translator->trans('pteroca.crud.menu.my_account'), 'fa fa-user')->setSubItems([
            MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.payments'), 'fa fa-money', UserPayment::class),
            MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.account_settings'), 'fa fa-user-cog', UserAccount::class),
        ]);

        if ($this->isGranted(UserRoleEnum::ROLE_ADMIN->name)) {
            yield MenuItem::section($this->translator->trans('pteroca.crud.menu.administration'));
            yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.overview'), 'fa fa-gauge', 'admin_overview');
            yield MenuItem::subMenu($this->translator->trans('pteroca.crud.menu.shop'), 'fa fa-shopping-cart')->setSubItems([
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.categories'), 'fa fa-list', Category::class),
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.products'), 'fa fa-sliders-h', Product::class),
            ]);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.servers'), 'fa fa-server', Server::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.payments'), 'fa fa-money', Payment::class);
            yield MenuItem::subMenu($this->translator->trans('pteroca.crud.menu.logs'), 'fa fa-bars-staggered')->setSubItems([
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.logs'), 'fa fa-bars-staggered', Log::class),
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.email_logs'), 'fa fa-envelope', EmailLog::class),
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.server_logs'), 'fa fa-bars', ServerLog::class),
            ]);
            yield MenuItem::subMenu($this->translator->trans('pteroca.crud.menu.settings'), 'fa fa-cogs')->setSubItems([
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.general'), 'fa fa-cog', $this->generateSettingsUrl(SettingContextEnum::GENERAL)),
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.pterodactyl'), 'fa fa-network-wired', $this->generateSettingsUrl(SettingContextEnum::PTERODACTYL)),
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.security'), 'fa fa-shield-halved', $this->generateSettingsUrl(SettingContextEnum::SECURITY)),
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.payment_gateways'), 'fa fa-hand-holding-dollar', $this->generateSettingsUrl(SettingContextEnum::PAYMENT)),
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.email'), 'fa fa-envelope', $this->generateSettingsUrl(SettingContextEnum::EMAIL)),
                MenuItem::linkToUrl($this->translator->trans('pteroca.crud.menu.appearance'), 'fa fa-brush', $this->generateSettingsUrl(SettingContextEnum::THEME)),
            ]);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.users'), 'fa fa-user', User::class);
            yield MenuItem::subMenu($this->translator->trans('pteroca.crud.menu.vouchers'), 'fa fa-gifts')->setSubItems([
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.vouchers'), 'fa fa-gift', Voucher::class),
                MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.voucher_usages'), 'fa fa-list', VoucherUsage::class),
            ]);
        }

        yield MenuItem::section();
        if ($this->settingService->getSetting(SettingEnum::SHOW_PHPMYADMIN_URL->value)) {
            yield MenuItem::linkToUrl(
                $this->translator->trans('pteroca.crud.menu.phpmyadmin'),
                'fa fa-database',
                $this->settingService->getSetting(SettingEnum::PHPMYADMIN_URL->value),
            )->setLinkTarget('_blank');
        }
        yield MenuItem::linkToLogout($this->translator->trans('pteroca.crud.menu.logout'), 'fa fa-sign-out-alt');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile(sprintf(
                '/assets/theme/%s/css/panel.css?v=%s',
                $this->templateManager->getCurrentTemplate(),
                $this->systemVersionService->getCurrentVersion(),
            ));
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenu = parent::configureUserMenu($user);
        $menuItems = $userMenu->getAsDto()->getItems();

        $logoutAction = end($menuItems);
        $logoutAction->getAsDto()->setIcon('fa-sign-out-alt');

        $userMenu->addMenuItems([
            MenuItem::linkToCrud(
                $this->translator->trans('pteroca.dashboard.account_settings'),
                'fa fa-user-cog',
                UserAccount::class
            ),
        ]);

        return $userMenu;
    }

    private function generateSettingsUrl(SettingContextEnum $context): string
    {
        $crudFqcn = match ($context) {
            SettingContextEnum::THEME => ThemeSettingCrudController::class,
            SettingContextEnum::SECURITY => SecuritySettingCrudController::class,
            SettingContextEnum::PAYMENT => PaymentSettingCrudController::class,
            SettingContextEnum::EMAIL => EmailSettingCrudController::class,
            SettingContextEnum::PTERODACTYL => PterodactylSettingCrudController::class,
            default => GeneralSettingCrudController::class,
        };

        return $this->generateUrl('panel', [
            'crudAction' => 'index',
            'crudControllerFqcn' => $crudFqcn,
        ]);
    }
}
