<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\LogService;
use App\Core\Service\Pterodactyl\PterodactylService;
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
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class ServerCrudController extends AbstractPanelController
{
    public function __construct(
        LogService $logService,
        private readonly PterodactylService $pterodactylService,
        private readonly LoggerInterface $logger,
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
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.server.save')))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.server.server'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.server.servers'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->value)
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
        $this->disallowForDemoMode();
        $pterodactylServer = $this->getPterodactylServerDetails($entityInstance->getPterodactylServerId());
        if ($entityInstance->getIsSuspended() !== $pterodactylServer->suspended) {
            if ($entityInstance->getIsSuspended()) {
                $this->pterodactylService->getApi()->servers->suspend($entityInstance->getPterodactylServerId());
            } else {
                $this->pterodactylService->getApi()->servers->unsuspend($entityInstance->getPterodactylServerId());
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->disallowForDemoMode();
        try {
            $this->pterodactylService->getApi()->servers->delete($entityInstance->getPterodactylServerId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete pterodactyl server during deleting entity', [
                'exception' => $e,
                'serverId' => $entityInstance->getPterodactylServerId(),
                'entityId' => $entityInstance->getId(),
            ]);
        }
        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function getPterodactylServerDetails(int $serverId): PterodactylServer
    {
        /** @var PterodactylServer $server */
        $server = $this->pterodactylService->getApi()->servers->get($serverId);
        return $server;
    }
}
