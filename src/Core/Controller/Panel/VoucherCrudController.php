<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Voucher;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Service\Crud\PanelCrudService;
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


class VoucherCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
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
                ->setColumns(4),
            TextField::new('code', $this->translator->trans('pteroca.crud.voucher.code'))
                ->setColumns(4),
            IntegerField::new('value', $this->translator->trans('pteroca.crud.voucher.value'))
                ->setColumns(4),
            TextareaField::new('description', $this->translator->trans('pteroca.crud.voucher.description'))
                ->hideOnIndex()
                ->setColumns(6),
            FormField::addRow(),
            IntegerField::new('minimumTopupAmount', $this->translator->trans('pteroca.crud.voucher.minimum_top_up_amount'))
                ->setColumns(2),
            IntegerField::new('minimumOrderAmount', $this->translator->trans('pteroca.crud.voucher.minimum_order_amount'))
                ->setColumns(2),
            IntegerField::new('maxGlobalUses', $this->translator->trans('pteroca.crud.voucher.max_global_uses'))
                ->setColumns(2),
            DateTimeField::new('expirationDate', $this->translator->trans('pteroca.crud.voucher.expiration_date'))
                ->setColumns(2),
            IntegerField::new('usedCount', $this->translator->trans('pteroca.crud.voucher.used_count'))
                ->setDisabled()
                ->hideWhenCreating(),
            FormField::addRow(),
            BooleanField::new('newAccountsOnly', $this->translator->trans('pteroca.crud.voucher.new_accounts_only'))
                ->hideOnIndex()
                ->setColumns(3),
            BooleanField::new('oneUsePerUser', $this->translator->trans('pteroca.crud.voucher.one_use_per_user'))
                ->hideOnIndex()
                ->setColumns(3),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
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
        ;
        return parent::configureFilters($filters);
    }
}
