<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Server;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Server\DeleteServerService;
use App\Core\Service\Server\UpdateServerService;
use App\Core\Service\SettingService;
use App\Core\Trait\CrudFlashMessagesTrait;
use App\Core\Trait\ManageServerActionTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerCrudController extends AbstractPanelController
{
    use ManageServerActionTrait;
    use CrudFlashMessagesTrait;

    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly UpdateServerService $updateServerService,
        private readonly DeleteServerService $deleteServerService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return Server::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            IntegerField::new('pterodactylServerId', $this->translator->trans('pteroca.crud.server.pterodactyl_server_id'))
                ->setDisabled()
                ->onlyOnForms(),
            TextField::new('pterodactylServerIdentifier', $this->translator->trans('pteroca.crud.server.pterodactyl_server_identifier'))
                ->setDisabled(),
            TextField::new('name', $this->translator->trans('pteroca.crud.server.name'))
                ->formatValue(function ($value, Server $entity) {
                    return $value ?: $entity->getServerProduct()->getName();
                }),
            AssociationField::new('serverProduct', $this->translator->trans('pteroca.crud.server.product_server_build'))
                ->setDisabled(),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.server.user'))
                ->setQueryBuilder(function ($queryBuilder) {
                    return $queryBuilder
                        ->andWhere('entity.deletedAt IS NULL');
                }),
            BooleanField::new('autoRenewal', $this->translator->trans('pteroca.crud.server.auto_renewal'))
                ->hideOnIndex(),

            NumberField::new('serverProduct.diskSpace', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.disk_space')))
                ->onlyOnDetail(),
            NumberField::new('serverProduct.memory', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.memory')))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.io', $this->translator->trans('pteroca.crud.product.io'))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.cpu', sprintf('%s (%%)', $this->translator->trans('pteroca.crud.product.cpu')))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.dbCount', $this->translator->trans('pteroca.crud.product.db_count'))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.swap', sprintf('%s (MB)', $this->translator->trans('pteroca.crud.product.swap')))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.backups', $this->translator->trans('pteroca.crud.product.backups'))
                ->onlyOnIndex(),
            NumberField::new('serverProduct.ports', $this->translator->trans('pteroca.crud.product.ports'))
                ->onlyOnIndex(),

            DateTimeField::new('expiresAt', $this->translator->trans('pteroca.crud.server.expires_at')),
            BooleanField::new('isSuspended', $this->translator->trans('pteroca.crud.server.is_suspended')),

            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.server.created_at'))->onlyOnDetail(),
            DateTimeField::new('deletedAt', $this->translator->trans('pteroca.crud.server.deleted_at'))->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                fn (Action $action) => $action->displayIf(
                    fn (Server $entity) => empty($entity->getDeletedAt())
                )
            )->update(
                Crud::PAGE_EDIT,
                Action::SAVE_AND_RETURN,
                fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.server.save')),
            )->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, $this->getServerProductAction(Crud::PAGE_EDIT))
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $this->getServerProductAction(Crud::PAGE_EDIT))
            ->add(Crud::PAGE_INDEX, $this->getManageServerAction())
            ->remove(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $this->getServerProductAction(Crud::PAGE_DETAIL))
            ->add(Crud::PAGE_INDEX, $this->getShowServerLogsAction())
            ->add(Crud::PAGE_EDIT, $this->getShowServerLogsAction())
            ->add(Crud::PAGE_DETAIL, $this->getManageServerAction())
            ->add(Crud::PAGE_EDIT, $this->getManageServerAction())
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::SERVER->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.server.server'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.server.servers'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC']);

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('pterodactylServerId')
            ->add('pterodactylServerIdentifier')
            ->add('user')
            ->add('expiresAt')
            ->add('isSuspended')
            ->add('autoRenewal')
            ->add('createdAt')
            ->add('deletedAt')
        ;

        return parent::configureFilters($filters);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setFlashMessages(
            $this->updateServerService
            ->updateServer($entityInstance)
            ->getMessages()
        );

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->deleteServerService->deleteServer($entityInstance);

        if ($entityInstance instanceof Server) {
            $entityInstance->setDeletedAtValue();
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function getServerProductAction(string $action): Action
    {
        return Action::new(
            sprintf('serverProduct_%s', $action),
            $this->translator->trans(sprintf('pteroca.crud.server.server_product_%s', $action)),
        )->linkToUrl(
            fn (Server $entity) => $this->generateUrl(
                'panel',
                [
                    'crudAction' => $action,
                    'crudControllerFqcn' => ServerProductCrudController::class,
                    'entityId' => $entity->getServerProduct()->getId(),
                ]
            )
        )->displayIf(function (Server $entity) use ($action) {
            if ($action !== Action::DETAIL) {
                return empty($entity->getDeletedAt());
            }

            return true;
        });
    }
}
