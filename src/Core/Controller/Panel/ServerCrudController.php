<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Server\DeleteServerService;
use App\Core\Service\Server\UpdateServerService;
use App\Core\Service\SettingService;
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
    public function __construct(
        LogService $logService,
        private readonly UpdateServerService $updateServerService,
        private readonly DeleteServerService $deleteServerService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($logService);
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
                ->setDisabled(),
            TextField::new('pterodactylServerIdentifier', $this->translator->trans('pteroca.crud.server.pterodactyl_server_identifier'))
                ->setDisabled(),
            AssociationField::new('product', $this->translator->trans('pteroca.crud.server.product'))
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
            ->add(Crud::PAGE_INDEX, $this->getManageServerAction())
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.server.server'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.server.servers'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('pterodactylServerId')
            ->add('product')
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

    private function getManageServerAction(): Action
    {
        $manageServerAction = Action::new(
            'manageServer',
            $this->translator->trans('pteroca.crud.server.manage_server'),
        );

        $usePterodactyl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value);
        if (empty($usePterodactyl)) {
            $manageServerAction->linkToRoute(
                'server',
                fn (Server $entity) => ['id' => $entity->getPterodactylServerIdentifier()]
            );
        } else {
            if ($this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_ENABLED->value)) {
                $manageServerAction->linkToRoute(
                    'sso_redirect',
                    fn (Server $entity) => [
                        'redirect_path' => sprintf('/server/%s', $entity->getPterodactylServerIdentifier()),
                    ]
                );
            } else {
                $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
                $manageServerAction->linkToUrl($pterodactylUrl);
            }
            $manageServerAction->setHtmlAttributes(['target' => '_blank']);
        }

        return $manageServerAction;
    }
}
