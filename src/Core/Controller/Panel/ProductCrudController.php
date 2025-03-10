<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Product;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductCrudController extends AbstractPanelController
{
    private array $flashMessages = [];

    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly PterodactylService $pterodactylService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $nests = $this->getNestsChoices();
        $uploadDirectory = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $this->getParameter('products_directory'),
        );
        $internalCurrency = $this->settingService
            ->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value);
        $fields = [
            FormField::addTab($this->translator->trans('pteroca.crud.product.details'))
                ->setIcon('fa fa-info-circle'),
            TextField::new('name', $this->translator->trans('pteroca.crud.product.name'))
                ->setColumns(7),
            NumberField::new('price', sprintf('%s (%s)', $this->translator->trans('pteroca.crud.product.price'), $internalCurrency)),
            TextareaField::new('description', $this->translator->trans('pteroca.crud.product.description'))
                ->setColumns(10),
            BooleanField::new('isActive', $this->translator->trans('pteroca.crud.product.is_active'))
                ->setColumns(12),
            AssociationField::new('category', $this->translator->trans('pteroca.crud.product.category'))
                ->setColumns(5),
            FormField::addRow(),
            ImageField::new('imagePath', $this->translator->trans('pteroca.crud.product.image'))
                ->setBasePath($this->getParameter('products_base_path'))
                ->setUploadDir($uploadDirectory)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false)
                ->setHelp($this->translator->trans('pteroca.crud.product.image_help'))
                ->setColumns(5),
            ImageField::new('bannerPath', $this->translator->trans('pteroca.crud.product.banner'))
                ->setBasePath($this->getParameter('products_base_path'))
                ->setUploadDir($uploadDirectory)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false)
                ->setHelp($this->translator->trans('pteroca.crud.product.banner_help'))
                ->setColumns(5),

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

            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.product.created_at'))->onlyOnDetail(),
            DateTimeField::new('updatedAt', $this->translator->trans('pteroca.crud.product.updated_at'))->onlyOnDetail(),

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
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.product.add')))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.product.add')))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.product.save')))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::PRODUCT->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.product.product'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.product.products'))
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
        ;

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('isActive')
            ->add('category')
            ->add('diskSpace')
            ->add('memory')
            ->add('io')
            ->add('cpu')
            ->add('dbCount')
            ->add('swap')
            ->add('backups')
            ->add('ports')
        ;
        return parent::configureFilters($filters);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $entityInstance->setEggsConfiguration(json_encode($this->getEggsConfigurationFromRequest()));
            $entityInstance->setCreatedAtValue();
            $entityInstance->setUpdatedAtValue();
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $entityInstance->setEggsConfiguration(json_encode($this->getEggsConfigurationFromRequest()));
            $entityInstance->setUpdatedAtValue();
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function getEggsConfigurationFromRequest(): array
    {
        $requestData = $this->requestStack->getCurrentRequest()->request->all();
        return $requestData['eggs_configuration'] ?? [];
    }

    private function getNodesChoices(): array
    {
        try {
            $nodes = $this->pterodactylService->getApi()->nodes->all()->toArray();
            $locations = [];
            $choices = [];

            foreach ($nodes as $node) {
                if (empty($locations[$node->location_id])) {
                    $locations[$node->location_id] = $this->pterodactylService
                        ->getApi()
                        ->locations
                        ->get($node->location_id);
                }
                $choices[$locations[$node->location_id]->short][$node->name] = $node->id;
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }

    private function getNestsChoices(): array
    {
        try {
            $nests = $this->pterodactylService->getApi()->nests->all()->toArray();
            $choices = [];

            foreach ($nests as $nest) {
                $choices[$nest->name] = $nest->id;
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }

    private function getEggsChoices(array $nests): array
    {
        try {
            $choices = [];
            foreach ($nests as $nestId) {
                $eggs = $this->pterodactylService->getApi()->nest_eggs->all($nestId)->toArray();
                foreach ($eggs as $egg) {
                    $choices[$egg->name] = $egg->id;
                }
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }
}
