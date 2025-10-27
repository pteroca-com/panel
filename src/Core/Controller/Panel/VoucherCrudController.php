<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Voucher;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Event\Voucher\VoucherCreationRequestedEvent;
use App\Core\Event\Voucher\VoucherCreatedEvent;
use App\Core\Event\Voucher\VoucherUpdateRequestedEvent;
use App\Core\Event\Voucher\VoucherUpdatedEvent;
use App\Core\Event\Voucher\VoucherDeletionRequestedEvent;
use App\Core\Event\Voucher\VoucherDeletedEvent;
use App\Core\Service\Crud\PanelCrudService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class VoucherCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Voucher::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            NumberField::new('id', 'ID')->onlyOnIndex(),
            ChoiceField::new('type', $this->translator->trans('pteroca.crud.voucher.type'))
                ->setChoices(VoucherTypeEnum::getChoices())
                ->setColumns(4)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.type_help')),
            TextField::new('code', $this->translator->trans('pteroca.crud.voucher.code'))
                ->setColumns(4)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.code_help')),
            IntegerField::new('value', $this->translator->trans('pteroca.crud.voucher.value'))
                ->setColumns(4)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.value_help')),
            TextareaField::new('description', $this->translator->trans('pteroca.crud.voucher.description'))
                ->hideOnIndex()
                ->setColumns(6)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.description_help')),
            IntegerField::new('minimumTopupAmount', $this->translator->trans('pteroca.crud.voucher.minimum_top_up_amount'))
                ->setColumns(3)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.minimum_top_up_amount_help')),
            FormField::addRow(),
            IntegerField::new('minimumOrderAmount', $this->translator->trans('pteroca.crud.voucher.minimum_order_amount'))
                ->setColumns(2)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.minimum_order_amount_help')),
            IntegerField::new('maxGlobalUses', $this->translator->trans('pteroca.crud.voucher.max_global_uses'))
                ->setColumns(2)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.max_global_uses_help')),
            DateTimeField::new('expirationDate', $this->translator->trans('pteroca.crud.voucher.expiration_date'))
                ->setColumns(2)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.expiration_date_help')),
            IntegerField::new('usedCount', $this->translator->trans('pteroca.crud.voucher.used_count'))
                ->setDisabled()
                ->hideWhenCreating()
                ->setHelp($this->translator->trans('pteroca.crud.voucher.used_count_help')),
            FormField::addRow(),
            BooleanField::new('newAccountsOnly', $this->translator->trans('pteroca.crud.voucher.new_accounts_only'))
                ->hideOnIndex()
                ->setColumns(3)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.new_accounts_only_help')),
            BooleanField::new('oneUsePerUser', $this->translator->trans('pteroca.crud.voucher.one_use_per_user'))
                ->hideOnIndex()
                ->setColumns(3)
                ->setHelp($this->translator->trans('pteroca.crud.voucher.one_use_per_user_help')),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, $this->getShowRedeemedVouchersAction())
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::VOUCHER->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.voucher.voucher'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.voucher.vouchers'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ;

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('id')
            ->add('type')
            ->add('code')
            ->add('value')
            ->add('minimumTopupAmount')
            ->add('minimumOrderAmount')
            ->add('maxGlobalUses')
            ->add('expirationDate')
            ->add('newAccountsOnly')
            ->add('oneUsePerUser')
            ->add('usedCount')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('deletedAt')
        ;

        return parent::configureFilters($filters);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (false === $entityInstance instanceof Voucher) {
            return;
        }

        if ($entityInstance->getType() === VoucherTypeEnum::BALANCE_TOPUP) {
            $entityInstance->setOneUsePerUser(true);
        }

        // Dispatch VoucherCreationRequestedEvent
        $request = $this->requestStack->getCurrentRequest();
        $eventContext = $request ? $this->buildMinimalEventContext($request) : [];

        $creationRequestedEvent = new VoucherCreationRequestedEvent($entityInstance, $eventContext);
        $creationRequestedEvent = $this->dispatchEvent($creationRequestedEvent);

        if ($creationRequestedEvent->isPropagationStopped()) {
            throw new \RuntimeException($this->translator->trans('pteroca.crud.voucher.creation_blocked'));
        }

        parent::persistEntity($entityManager, $entityInstance);

        // Dispatch VoucherCreatedEvent
        $createdEvent = new VoucherCreatedEvent($entityInstance, $eventContext);
        $this->dispatchEvent($createdEvent);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (false === $entityInstance instanceof Voucher) {
            return;
        }

        if ($entityInstance->getType() === VoucherTypeEnum::BALANCE_TOPUP) {
            $entityInstance->setOneUsePerUser(true);
        }

        // Dispatch VoucherUpdateRequestedEvent
        $request = $this->requestStack->getCurrentRequest();
        $eventContext = $request ? $this->buildMinimalEventContext($request) : [];

        $updateRequestedEvent = new VoucherUpdateRequestedEvent($entityInstance, $eventContext);
        $updateRequestedEvent = $this->dispatchEvent($updateRequestedEvent);

        if ($updateRequestedEvent->isPropagationStopped()) {
            throw new \RuntimeException($this->translator->trans('pteroca.crud.voucher.update_blocked'));
        }

        parent::updateEntity($entityManager, $entityInstance);

        // Dispatch VoucherUpdatedEvent
        $updatedEvent = new VoucherUpdatedEvent($entityInstance, $eventContext);
        $this->dispatchEvent($updatedEvent);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (false === $entityInstance instanceof Voucher) {
            return;
        }

        // Dispatch VoucherDeletionRequestedEvent
        $request = $this->requestStack->getCurrentRequest();
        $eventContext = $request ? $this->buildMinimalEventContext($request) : [];

        $deletionRequestedEvent = new VoucherDeletionRequestedEvent($entityInstance, $eventContext);
        $deletionRequestedEvent = $this->dispatchEvent($deletionRequestedEvent);

        if ($deletionRequestedEvent->isPropagationStopped()) {
            throw new \RuntimeException($this->translator->trans('pteroca.crud.voucher.deletion_blocked'));
        }

        // Store voucher data before deletion
        $voucherId = $entityInstance->getId();
        $voucherCode = $entityInstance->getCode();

        parent::deleteEntity($entityManager, $entityInstance);

        // Dispatch VoucherDeletedEvent
        $deletedEvent = new VoucherDeletedEvent($voucherId, $voucherCode, $eventContext);
        $this->dispatchEvent($deletedEvent);
    }

    private function getShowRedeemedVouchersAction(): Action
    {
        return Action::new(
            'showVoucherUsages',
            $this->translator->trans('pteroca.crud.voucher.show_voucher_usages')
        )->linkToUrl(
            fn (Voucher $entity) => $this->generateUrl(
                'panel',
                [
                    'crudAction' => 'index',
                    'crudControllerFqcn' => VoucherUsageCrudController::class,
                    'filters' => [
                        'voucher' => [
                            'comparison' => '=',
                            'value' => $entity->getId(),
                        ]
                    ]
                ]
            )
        );
    }
}
