<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Category;
use App\Core\Entity\Log;
use App\Core\Entity\Payment;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\Setting;
use App\Core\Entity\User;
use App\Core\Entity\UserAccount;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\LogService;
use App\Core\Service\SettingService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
        private readonly ServerRepository $serverRepository,
        private readonly LogService $logService,
    ) {}

    #[Route('/panel', name: 'panel')]
    public function index(): Response
    {
        $user = $this->getUser();
        $pterodactylPanelUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
        return $this->render('panel/dashboard/dashboard.html.twig', [
            'servers' => $this->serverRepository->findBy(['user' => $user]),
            'user' => $user,
            'logs' => $this->logService->getLogsByUser($user, 10),
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
        return Dashboard::new()
            ->setTitle($logo);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section($this->translator->trans('pteroca.crud.menu.menu'));
        yield MenuItem::linkToDashboard($this->translator->trans('pteroca.crud.menu.dashboard'), 'fa fa-home');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.servers'), 'fa fa-server', 'servers');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.shop'), 'fa fa-shopping-cart', 'store');
        yield MenuItem::linkToRoute($this->translator->trans('pteroca.crud.menu.wallet'), 'fa fa-wallet', 'recharge_balance');

        if ($this->isGranted(UserRoleEnum::ROLE_ADMIN->name)) {
            yield MenuItem::section($this->translator->trans('pteroca.crud.menu.administration'));
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.categories'), 'fa fa-list', Category::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.products'), 'fa fa-sliders-h', Product::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.payments'), 'fa fa-money', Payment::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.logs'), 'fa fa-bars-staggered', Log::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.servers'), 'fa fa-server', Server::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.settings'), 'fa fa-cogs', Setting::class);
            yield MenuItem::linkToCrud($this->translator->trans('pteroca.crud.menu.users'), 'fa fa-user', User::class);
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
            ->addCssFile('/assets/css/panel.css')
            ;
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenu = parent::configureUserMenu($user);
        $userMenu->addMenuItems([
            MenuItem::linkToCrud(
                $this->translator->trans('pteroca.dashboard.account_settings'),
                'fa fa-user-cog',
                UserAccount::class
            ),
        ]);
        return $userMenu;
    }
}
