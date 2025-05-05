<?php
namespace App\Core\Controller\Panel;

use App\Core\Entity\Payment;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('sessionId', $this->translator->trans('pteroca.crud.payment.session_id')),
            TextField::new('status', $this->translator->trans('pteroca.crud.payment.status'))
                ->formatValue(fn ($value) => sprintf(
                    "<span class='badge %s'>%s</span>",
                    $value === 'paid' ? 'badge-success' : 'badge-danger',
                    $value,
                )),
            NumberField::new('amount', $this->translator->trans('pteroca.crud.payment.amount'))
                ->setNumDecimals(2),
            TextField::new('currency', $this->translator->trans('pteroca.crud.payment.currency'))
                ->formatValue(fn ($value) => strtoupper($value)),
            NumberField::new('balanceAmount', $this->translator->trans('pteroca.crud.payment.balance_amount'))
                ->setNumDecimals(2),
            AssociationField::new('usedVoucher', $this->translator->trans('pteroca.crud.payment.used_voucher')),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.payment.user')),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.payment.created_at')),
            DateTimeField::new('updatedAt', $this->translator->trans('pteroca.crud.payment.updated_at')),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::PAYMENT->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.payment.payment'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.payment.payments'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('sessionId')
            ->add('status')
            ->add('user')
            ->add('amount')
            ->add('currency')
            ->add('balanceAmount')
            ->add('usedVoucher')
            ->add('createdAt')
            ->add('updatedAt')
        ;
        return parent::configureFilters($filters);
    }
}
