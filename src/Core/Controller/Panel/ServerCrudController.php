<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Server;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Server\DeleteServerService;
use App\Core\Service\Server\UpdateServerService;
use App\Core\Service\SettingService;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerCrudController extends AbstractPanelController
{
    use ManageServerActionTrait;

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
            AssociationField::new('serverProduct', $this->translator->trans('pteroca.crud.server.product_server_build'))
                ->setDisabled(),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.server.user')),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.server.created_at'))
                ->hideOnForm(),
            DateTimeField::new('expiresAt', $this->translator->trans('pteroca.crud.server.expires_at')),
            BooleanField::new('isSuspended', $this->translator->trans('pteroca.crud.server.is_suspended')),
            BooleanField::new('autoRenewal', $this->translator->trans('pteroca.crud.server.auto_renewal'))
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->update(
                Crud::PAGE_EDIT,
                Action::SAVE_AND_RETURN,
                fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.server.save')),
            )
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, $this->getServerProductAction(Crud::PAGE_EDIT))
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $this->getServerProductAction(Crud::PAGE_EDIT))
            ->add(Crud::PAGE_INDEX, $this->getManageServerAction())
            ->remove(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $this->getServerAction(Crud::PAGE_DETAIL))
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
            ->add('user')
            ->add('createdAt')
            ->add('expiresAt')
            ->add('isSuspended')
        ;
        return parent::configureFilters($filters);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->updateServerService->updateServer($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->deleteServerService->deleteServer($entityInstance);
        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function getServerProductAction(string $action): Action
    {
        return Action::new(
            'serverProductEdit',
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
        );
    }
}
