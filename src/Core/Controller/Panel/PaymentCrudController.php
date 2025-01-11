<?php
namespace App\Core\Controller\Panel;

use App\Core\Entity\Payment;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Logs\LogService;
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
        LogService $logService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($logService);
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
                ->setNumDecimals(2)
                ->formatValue(fn ($value) => number_format($value / 100, 2)),
            TextField::new('currency', $this->translator->trans('pteroca.crud.payment.currency'))
                ->formatValue(fn ($value) => strtoupper($value)),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.payment.user')),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.payment.created_at')),
            DateTimeField::new('updatedAt', $this->translator->trans('pteroca.crud.payment.updated_at')),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.payment.payment'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.payment.payments'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('sessionId')
            ->add('status')
            ->add('amount')
            ->add('currency')
            ->add('user')
            ->add('createdAt')
            ->add('updatedAt')
        ;
        return parent::configureFilters($filters);
    }
}
