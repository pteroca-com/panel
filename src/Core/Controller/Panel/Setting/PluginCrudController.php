<?php

namespace App\Core\Controller\Panel\Setting;

use App\Core\Controller\Panel\AbstractPanelController;
use App\Core\Entity\Plugin;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\PluginStateEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Plugin\PluginDetailsDataLoadedEvent;
use App\Core\Event\Plugin\PluginDetailsPageAccessedEvent;
use App\Core\Event\Plugin\PluginDisablementFailedEvent;
use App\Core\Event\Plugin\PluginDisablementRequestedEvent;
use App\Core\Event\Plugin\PluginEnablementFailedEvent;
use App\Core\Event\Plugin\PluginEnablementRequestedEvent;
use App\Core\Event\Plugin\PluginIndexDataLoadedEvent;
use App\Core\Event\Plugin\PluginIndexPageAccessedEvent;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Plugin\PluginManager;
use App\Core\Service\Plugin\PluginDependencyResolver;
use App\Core\Exception\Plugin\PluginDependencyException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Composer\Semver\Semver;

class PluginCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        RequestStack $requestStack,
        private readonly PluginManager $pluginManager,
        private readonly TranslatorInterface $translator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly LogService $logService,
        private readonly PluginDependencyResolver $dependencyResolver,
    ) {
        parent::__construct($panelCrudService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Plugin::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', $this->translator->trans('pteroca.crud.plugin.name'))
                ->hideOnForm(),
            TextField::new('displayName', $this->translator->trans('pteroca.crud.plugin.display_name'))
                ->hideOnForm(),
            TextField::new('version', $this->translator->trans('pteroca.crud.plugin.version'))
                ->setColumns(2),
            TextField::new('author', $this->translator->trans('pteroca.crud.plugin.author'))
                ->setColumns(2)
                ->hideOnIndex(),
            TextField::new('description', $this->translator->trans('pteroca.crud.plugin.description'))
                ->hideOnIndex(),
            TextField::new('state', $this->translator->trans('pteroca.crud.plugin.state'))
                ->setColumns(2)
                ->formatValue(function ($value, $entity) {
                    return sprintf(
                        '<span class="badge bg-%s">%s</span>',
                        $value->getBadgeClass(),
                        $value->getLabel()
                    );
                }),
            ArrayField::new('capabilities', $this->translator->trans('pteroca.crud.plugin.capabilities'))
                ->hideOnForm()
                ->hideOnIndex(),
            TextField::new('pterocaMinVersion', $this->translator->trans('pteroca.crud.plugin.min_pteroca'))
                ->setColumns(2)
                ->hideOnIndex(),
            TextField::new('pterocaMaxVersion', $this->translator->trans('pteroca.crud.plugin.max_pteroca'))
                ->setColumns(2)
                ->hideOnIndex(),
            DateTimeField::new('enabledAt', $this->translator->trans('pteroca.crud.plugin.enabled_at'))
                ->hideOnForm()
                ->hideOnIndex(),
            DateTimeField::new('disabledAt', $this->translator->trans('pteroca.crud.plugin.disabled_at'))
                ->hideOnForm()
                ->hideOnIndex(),
            TextField::new('faultReason', $this->translator->trans('pteroca.crud.plugin.fault_reason'))
                ->hideOnForm()
                ->hideOnIndex(),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.plugin.created_at'))
                ->hideOnForm()
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $settingsAction = Action::new('settings', $this->translator->trans('pteroca.crud.plugin.settings'), 'fa fa-cog')
            ->linkToUrl(function (Plugin $plugin) {
                return $this->adminUrlGenerator
                    ->setController(PluginSettingCrudController::class)
                    ->setAction(Action::INDEX)
                    ->set('pluginName', $plugin->getName())
                    ->generateUrl();
            })
            ->displayIf(static function (Plugin $plugin) {
                // Show settings for plugins that have been enabled at least once (have DB entry)
                return $plugin->getId() !== null;
            })
            ->setCssClass('btn btn-secondary');

        $enableAction = Action::new('enable', $this->translator->trans('pteroca.crud.plugin.enable'), 'fa fa-check')
            ->linkToCrudAction('enablePlugin')
            ->displayIf(static function (Plugin $plugin) {
                $state = $plugin->getState();
                return in_array($state, [PluginStateEnum::DISCOVERED, PluginStateEnum::DISABLED], true);
            })
            ->setCssClass('btn btn-success');

        $disableAction = Action::new('disable', $this->translator->trans('pteroca.crud.plugin.disable'), 'fa fa-times')
            ->linkToCrudAction('disablePlugin')
            ->displayIf(static function (Plugin $plugin) {
                return $plugin->getState() === PluginStateEnum::ENABLED;
            })
            ->setCssClass('btn btn-warning');

        return $actions
            ->add(Crud::PAGE_INDEX, $settingsAction)
            ->add(Crud::PAGE_INDEX, $enableAction)
            ->add(Crud::PAGE_INDEX, $disableAction)
            ->add(Crud::PAGE_DETAIL, $settingsAction)
            ->add(Crud::PAGE_DETAIL, $enableAction)
            ->add(Crud::PAGE_DETAIL, $disableAction)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::SETTING->value);
        $this->appendCrudTemplateContext('plugin');

        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.plugin.plugin'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.plugin.plugins'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['name' => 'ASC'])
            ->setPageTitle(Crud::PAGE_INDEX, $this->translator->trans('pteroca.crud.plugin.plugin_management'))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Plugin $plugin) => sprintf('%s: %s', $this->translator->trans('pteroca.crud.plugin.plugin'), $plugin->getDisplayName()))
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('displayName')
            ->add('state')
            ->add('author');
    }

    public function index(AdminContext $context): Response
    {
        $request = $context->getRequest();

        $this->dispatchSimpleEvent(PluginIndexPageAccessedEvent::class, $request);

        $plugins = $this->pluginManager->getAllPluginsFromFilesystem();
        foreach ($plugins as $plugin) {
            if ($plugin->getId() !== null && $plugin->getState() === PluginStateEnum::UPDATE_PENDING) {
                $this->pluginManager->getPluginByName($plugin->getName()); // This will save the update
            }
        }

        $this->dispatchDataEvent(
            PluginIndexDataLoadedEvent::class,
            $request,
            [$plugins, count($plugins)]
        );

        $viewData = [
            'plugins' => $plugins,
            'pageName' => Crud::PAGE_INDEX,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::PLUGIN_INDEX,
            'admin/plugin/index.html.twig',
            $viewData,
            $request
        );
    }

    public function viewDetails(AdminContext $context): Response
    {
        $request = $context->getRequest();
        $pluginName = $request->query->get('pluginName');

        $this->dispatchDataEvent(
            PluginDetailsPageAccessedEvent::class,
            $request,
            [$pluginName, null, null]
        );

        $plugin = $this->pluginManager->getPluginByName($pluginName);

        if ($plugin === null) {
            // Plugin not in DB, try to get from filesystem
            $allPlugins = $this->pluginManager->getAllPluginsFromFilesystem();
            foreach ($allPlugins as $p) {
                if ($p->getName() === $pluginName) {
                    $plugin = $p;
                    break;
                }
            }
        }

        if ($plugin === null) {
            $this->addFlash('danger', sprintf(
                $this->translator->trans('pteroca.crud.plugin.plugin_not_found'),
                $pluginName
            ));
            return $this->redirectToRoute('admin');
        }

        $this->dispatchDataEvent(
            PluginDetailsDataLoadedEvent::class,
            $request,
            [$pluginName, $plugin]
        );

        // Build dependency information for template
        $dependencies = [];
        $requires = $plugin->getRequires();

        foreach ($requires as $depName => $constraint) {
            $depPlugin = $this->pluginManager->getPluginByName($depName);
            $dependencies[] = [
                'name' => $depName,
                'constraint' => $constraint,
                'plugin' => $depPlugin,
                'installed' => $depPlugin !== null,
                'enabled' => $depPlugin && $depPlugin->isEnabled(),
                'version' => $depPlugin ? $depPlugin->getVersion() : null,
                'compatible' => $depPlugin && Semver::satisfies($depPlugin->getVersion(), $constraint),
            ];
        }

        // Get dependents (plugins that depend on this one)
        $dependents = $this->dependencyResolver->getDependents($plugin);

        // Check for circular dependencies
        $hasCircular = $this->dependencyResolver->hasCircularDependency($plugin);
        $circularPath = $hasCircular ? $this->dependencyResolver->getCircularDependencyPath($plugin) : null;

        $viewData = [
            'plugin' => $plugin,
            'pageName' => Crud::PAGE_DETAIL,
            'dependencies' => $dependencies,
            'dependents' => $dependents,
            'hasCircularDependency' => $hasCircular,
            'circularDependencyPath' => $circularPath,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::PLUGIN_DETAILS,
            'admin/plugin/detail.html.twig',
            $viewData,
            $request
        );
    }

    public function enablePlugin(AdminContext $context): RedirectResponse
    {
        $request = $context->getRequest();
        $pluginName = $request->query->get('pluginName');

        $this->dispatchDataEvent(
            PluginEnablementRequestedEvent::class,
            $request,
            [$pluginName, null]
        );

        try {
            $pluginEntity = $this->pluginManager->getOrCreatePlugin($pluginName);

            $this->pluginManager->enablePlugin($pluginEntity);

            $this->logService->logAction(
                $this->getUser(),
                LogActionEnum::PLUGIN_ENABLED,
                ['plugin' => $pluginName]
            );

            $this->addFlash('success', sprintf(
                $this->translator->trans('pteroca.crud.plugin.plugin_enabled_successfully'),
                $pluginEntity->getDisplayName()
            ));
        } catch (PluginDependencyException $e) {
            // Specialized handling for dependency errors
            $this->dispatchDataEvent(
                PluginEnablementFailedEvent::class,
                $request,
                [$pluginName, $e->getMessage()]
            );

            $this->addFlash('danger', sprintf(
                '%s:<br>%s',
                $this->translator->trans('pteroca.crud.plugin.dependency_error'),
                nl2br(htmlspecialchars($e->getMessage()))
            ));
        } catch (\Exception $e) {
            $this->dispatchDataEvent(
                PluginEnablementFailedEvent::class,
                $request,
                [$pluginName, $e->getMessage()]
            );

            $this->addFlash('danger', sprintf(
                $this->translator->trans('pteroca.crud.plugin.failed_to_enable_plugin'),
                $e->getMessage()
            ));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }

    public function disablePlugin(AdminContext $context): RedirectResponse
    {
        $request = $context->getRequest();
        $pluginName = $request->query->get('pluginName');

        $this->dispatchDataEvent(
            PluginDisablementRequestedEvent::class,
            $request,
            [$pluginName, null]
        );

        try {
            $pluginEntity = $this->pluginManager->getPluginByName($pluginName);

            if ($pluginEntity === null) {
                throw new \RuntimeException('Plugin not found in database');
            }

            $this->pluginManager->disablePlugin($pluginEntity, false); // cascade=false by default in UI

            $this->logService->logAction(
                $this->getUser(),
                LogActionEnum::PLUGIN_DISABLED,
                ['plugin' => $pluginName]
            );

            $this->addFlash('success', sprintf(
                $this->translator->trans('pteroca.crud.plugin.plugin_disabled_successfully'),
                $pluginEntity->getDisplayName()
            ));
        } catch (PluginDependencyException $e) {
            // Specialized handling for dependency errors (dependents exist)
            $this->dispatchDataEvent(
                PluginDisablementFailedEvent::class,
                $request,
                [$pluginName, $e->getMessage()]
            );

            $this->addFlash('warning', sprintf(
                '%s',
                nl2br(htmlspecialchars($e->getMessage()))
            ));
        } catch (\Exception $e) {
            $this->dispatchDataEvent(
                PluginDisablementFailedEvent::class,
                $request,
                [$pluginName, $e->getMessage()]
            );

            $this->addFlash('danger', sprintf(
                $this->translator->trans('pteroca.crud.plugin.failed_to_disable_plugin'),
                $e->getMessage()
            ));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }
}
