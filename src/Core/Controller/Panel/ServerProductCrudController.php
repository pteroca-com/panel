<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\ServerProduct;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Form\ServerProductPriceDynamicFormType;
use App\Core\Form\ServerProductPriceFixedFormType;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\UpdateServerService;
use App\Core\Service\SettingService;
use App\Core\Trait\ManageServerActionTrait;
use App\Core\Trait\ProductCrudControllerTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerProductCrudController extends AbstractPanelController
{
    use ProductCrudControllerTrait;
    use ManageServerActionTrait;

    private array $flashMessages = [];

    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly PterodactylService $pterodactylService,
        private readonly UpdateServerService $updateServerService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return ServerProduct::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $nests = $this->getNestsChoices();
        $internalCurrency = $this->settingService
            ->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value);
        $fields = [
            FormField::addTab($this->translator->trans('pteroca.crud.product.server_details'))
                ->setIcon('fa fa-info-circle'),
            IdField::new('server.id')
                ->hideOnForm(),
            IntegerField::new('server.pterodactylServerId', $this->translator->trans('pteroca.crud.server.pterodactyl_server_id'))
                ->setDisabled(),
            TextField::new('server.pterodactylServerIdentifier', $this->translator->trans('pteroca.crud.server.pterodactyl_server_identifier'))
                ->setDisabled(),
            TextField::new('server.user', $this->translator->trans('pteroca.crud.server.user'))
                ->setDisabled(),
            DateTimeField::new('server.createdAt', $this->translator->trans('pteroca.crud.server.created_at'))
                ->hideOnForm(),
            DateTimeField::new('server.expiresAt', $this->translator->trans('pteroca.crud.server.expires_at')),
            BooleanField::new('server.isSuspended', $this->translator->trans('pteroca.crud.server.is_suspended')),
            BooleanField::new('server.autoRenewal', $this->translator->trans('pteroca.crud.server.auto_renewal'))
                ->hideOnIndex(),

            FormField::addTab($this->translator->trans('pteroca.crud.product.build_details'))
                ->setIcon('fa fa-info-circle'),
            TextField::new('name', $this->translator->trans('pteroca.crud.product.build_name'))
                ->setColumns(7),
            FormField::addRow(),
            AssociationField::new('originalProduct', $this->translator->trans('pteroca.crud.product.original_product'))
                ->setColumns(7)
                ->setDisabled(),
            AssociationField::new('server', $this->translator->trans('pteroca.crud.product.server'))
                ->setColumns(5)
                ->setDisabled(),

            FormField::addTab($this->translator->trans('pteroca.crud.product.server_resources'))
                ->setIcon('fa fa-server'),
            NumberField::new('diskSpace', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.disk_space')))
                ->setHelp($this->translator->trans('pteroca.crud.product.disk_space_hint'))
                ->setColumns(4),
            NumberField::new('memory', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.memory')))
                ->setHelp($this->translator->trans('pteroca.crud.product.memory_hint'))
                ->setColumns(4),
            FormField::addRow(),
            NumberField::new('io', $this->translator->trans('pteroca.crud.product.io'))
                ->setHelp($this->translator->trans('pteroca.crud.product.io_hint'))
                ->setColumns(4),
            NumberField::new('cpu', sprintf('%s (%%)', $this->translator->trans('pteroca.crud.product.cpu')))
                ->setHelp($this->translator->trans('pteroca.crud.product.cpu_hint'))
                ->setColumns(4),
            FormField::addRow(),
            NumberField::new('dbCount', $this->translator->trans('pteroca.crud.product.db_count'))
                ->setHelp($this->translator->trans('pteroca.crud.product.db_count_hint'))
                ->setColumns(4),
            NumberField::new('swap', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.swap')))
                ->setHelp($this->translator->trans('pteroca.crud.product.swap_hint'))
                ->setColumns(4),
            FormField::addRow(),
            NumberField::new('backups', $this->translator->trans('pteroca.crud.product.backups'))
                ->setHelp($this->translator->trans('pteroca.crud.product.backups_hint'))
                ->setColumns(4),
            NumberField::new('ports', $this->translator->trans('pteroca.crud.product.ports'))
                ->setHelp($this->translator->trans('pteroca.crud.product.ports_hint'))
                ->setColumns(4),

            FormField::addTab($this->translator->trans('pteroca.crud.product.product_connections'))
                ->setIcon('fa fa-link'),
            ChoiceField::new('nodes', $this->translator->trans('pteroca.crud.product.nodes'))
                ->setHelp($this->translator->trans('pteroca.crud.product.nodes_hint'))
                ->setChoices(fn () => $this->getNodesChoices())
                ->allowMultipleChoices()
                ->setRequired(true)
                ->onlyOnForms()
                ->setColumns(6),
            ChoiceField::new('nest', $this->translator->trans('pteroca.crud.product.nest'))
                ->setHelp($this->translator->trans('pteroca.crud.product.nest_hint'))
                ->setChoices(fn () => $nests)
                ->onlyOnForms()
                ->setRequired(true)
                ->setFormTypeOption('attr', ['class' => 'nest-selector'])
                ->setColumns(6),
            HiddenField::new('eggsConfiguration')->onlyOnForms(),
            BooleanField::new('allowChangeEgg', $this->translator->trans('pteroca.crud.product.egg_allow_change'))
                ->setRequired(false)
                ->hideOnIndex(),
            ChoiceField::new('eggs', $this->translator->trans('pteroca.crud.product.eggs'))
                ->setHelp($this->translator->trans('pteroca.crud.product.eggs_hint'))
                ->setChoices(fn() => $this->getEggsChoices($nests))
                ->allowMultipleChoices()
                ->onlyOnForms()
                ->setRequired(true)
                ->setFormTypeOption('attr', ['class' => 'egg-selector'])
                ->setColumns(12),

            FormField::addTab($this->translator->trans('pteroca.crud.product.pricing'))
                ->setIcon('fa fa-money'),
            CollectionField::new('staticPrices', sprintf('%s (%s)', $this->translator->trans('pteroca.crud.product.price_static_plan'), $internalCurrency))
                ->setEntryType(ServerProductPriceFixedFormType::class)
                ->allowAdd()
                ->allowDelete()
                ->onlyOnForms()
                ->setColumns(6)
                ->setHelp($this->translator->trans('pteroca.crud.product.price_static_plan_hint'))
                ->setRequired(true)
                ->setEntryIsComplex(),
            CollectionField::new('dynamicPrices', sprintf('%s (%s)', $this->translator->trans('pteroca.crud.product.price_dynamic_plan'), $internalCurrency))
                ->setEntryType(ServerProductPriceDynamicFormType::class)
                ->allowAdd()
                ->allowDelete()
                ->setSortable(true)
                ->onlyOnForms()
                ->setColumns(6)
                ->setHelp($this->translator->trans('pteroca.crud.product.price_dynamic_plan_hint'))
                ->setRequired(true)
                ->setEntryIsComplex(),
        ];

        if (!empty($this->flashMessages)) {
            $flashMessages = implode(PHP_EOL, $this->flashMessages);
            $this->addFlash('danger', $flashMessages);
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Crud::PAGE_INDEX)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, $this->getServerAction(Crud::PAGE_EDIT))
            ->add(Crud::PAGE_EDIT, $this->getServerAction(Crud::PAGE_EDIT))
            ->add(Crud::PAGE_EDIT, $this->getManageServerAction())
            ->add(Crud::PAGE_DETAIL, $this->getManageServerAction())
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::SERVER_PRODUCT->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.product.server_build'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.product.server_builds'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setSearchFields(null)
        ;

        return parent::configureCrud($crud);
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        return $this->redirect($this->generateUrl('panel', [
            'crudControllerFqcn' => ServerCrudController::class,
            'crudAction' => 'index',
        ]));
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof ServerProduct) {
            $entityInstance->setEggsConfiguration(json_encode($this->getEggsConfigurationFromRequest()));
        }

        $this->updateServerService->updateServer($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof ServerProduct) {
            $entityInstance->setEggsConfiguration(json_encode($this->getEggsConfigurationFromRequest()));
        }

        $this->updateServerService->updateServer($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }
}
