<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\VoucherUsage;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Contracts\Translation\TranslatorInterface;


class VoucherUsageCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return VoucherUsage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            NumberField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('voucher', $this->translator->trans('pteroca.crud.voucher_usage.voucher_code')),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.voucher_usage.user')),
            DateTimeField::new('usedAt', $this->translator->trans('pteroca.crud.voucher_usage.used_at'))
                ->setFormat('dd/MM/yyyy HH:mm:ss')
                ->setColumns(4),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::VOUCHER_USAGE->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.voucher_usage.voucher_usage'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.voucher_usage.voucher_usages'))
            ->showEntityActionsInlined()
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
        ;

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('id')
            ->add('voucher')
            ->add('user')
            ->add('usedAt')
        ;
        return parent::configureFilters($filters);
    }
}
