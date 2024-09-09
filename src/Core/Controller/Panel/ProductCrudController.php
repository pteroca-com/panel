<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Product;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\LogService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductCrudController extends AbstractPanelController
{
    private array $flashMessages = [];

    public function __construct(
        LogService $logService,
        private readonly PterodactylService $pterodactylService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($logService);
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        $assets->addJsFile('assets/js/product.js');
        return parent::configureAssets($assets);
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
            TextField::new('name', $this->translator->trans('pteroca.crud.product.name')),
            TextareaField::new('description', $this->translator->trans('pteroca.crud.product.description')),
            NumberField::new('price', sprintf('%s (%s)', $this->translator->trans('pteroca.crud.product.price'), $internalCurrency)),
            BooleanField::new('isActive', $this->translator->trans('pteroca.crud.product.is_active')),
            AssociationField::new('category', $this->translator->trans('pteroca.crud.product.category')),
            ImageField::new('imagePath', $this->translator->trans('pteroca.crud.product.image'))
                ->setBasePath($this->getParameter('products_base_path'))
                ->setUploadDir($uploadDirectory)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false),

            FormField::addTab($this->translator->trans('pteroca.crud.product.server_resources'))
                ->setIcon('fa fa-server'),
            NumberField::new('diskSpace', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.disk_space'))),
            NumberField::new('memory', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.memory'))),
            NumberField::new('io', $this->translator->trans('pteroca.crud.product.io')),
            NumberField::new('cpu', sprintf('%s (%%)', $this->translator->trans('pteroca.crud.product.cpu'))),
            NumberField::new('dbCount', $this->translator->trans('pteroca.crud.product.db_count')),
            NumberField::new('swap', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.swap'))),
            NumberField::new('backups', $this->translator->trans('pteroca.crud.product.backups')),
            NumberField::new('ports', $this->translator->trans('pteroca.crud.product.ports')),

            FormField::addTab($this->translator->trans('pteroca.crud.product.product_connections'))
                ->setIcon('fa fa-link'),
            ChoiceField::new('nodes', $this->translator->trans('pteroca.crud.product.nodes'))
                ->setChoices(fn () => $this->getNodesChoices())
                ->allowMultipleChoices()
                ->setRequired(true)
                ->onlyOnForms(),
            ChoiceField::new('nest', $this->translator->trans('pteroca.crud.product.nest'))
                ->setChoices(fn () => $nests)
                ->onlyOnForms()
                ->setRequired(true)
                ->setFormTypeOption('attr', ['class' => 'nest-selector']),
            ChoiceField::new('eggs', $this->translator->trans('pteroca.crud.product.eggs'))
                ->setChoices(fn() => $this->getEggsChoices($nests))
                ->allowMultipleChoices()
                ->onlyOnForms()
                ->setRequired(true)
                ->setFormTypeOption('attr', ['class' => 'egg-selector']),

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
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.product.product'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.product.products'))
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name);
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
        $this->disallowForDemoMode();

        if ($entityInstance instanceof Product) {
            $entityInstance->setCreatedAtValue();
            $entityInstance->setUpdatedAtValue();
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $entityInstance->setUpdatedAtValue();
        }

        parent::updateEntity($entityManager, $entityInstance);
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
