<?php
namespace App\Core\Controller;

use App\Core\Controller\Panel\AbstractPanelController;
use App\Core\Entity\Panel\UserPayment;
use App\Core\Entity\Payment;
use App\Core\Entity\Voucher;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\PaymentStatusEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Exception\PaymentExpiredException;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\SettingService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserPaymentCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
        private readonly PaymentService $paymentService,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return UserPayment::class;
    }

    public function continuePayment(AdminContext $context): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();
        if (!$entity instanceof Payment) {
            throw new \Exception('Invalid entity type');
        }

        try {
            $continuePaymentUrl = $this->paymentService->continuePayment($entity->getSessionId());
        } catch (PaymentExpiredException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('panel', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]);
        }

        return $this->redirect($continuePaymentUrl);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sessionId', $this->translator->trans('pteroca.crud.payment.session_id')),
            TextField::new('status', $this->translator->trans('pteroca.crud.payment.status'))
                ->formatValue(fn ($value) => sprintf(
                    "<span class='badge %s'>%s</span>",
                    $value === 'paid' ? 'badge-success' : 'badge-danger',
                    $value === 'paid' 
                        ? $this->translator->trans('pteroca.recharge.transaction_paid')
                        : $this->translator->trans('pteroca.recharge.transaction_unpaid'),
                )),
            TextField::new('amountWithCurrency', $this->translator->trans('pteroca.crud.payment.amount'))
                ->onlyOnIndex(),
            NumberField::new('amount', $this->translator->trans('pteroca.crud.payment.amount'))
                ->onlyOnDetail()
                ->setNumDecimals(2),
            TextField::new('currency', $this->translator->trans('pteroca.crud.payment.currency'))
                ->onlyOnDetail()
                ->formatValue(fn ($value) => strtoupper($value)),
            NumberField::new('balanceAmount', $this->translator->trans('pteroca.crud.payment.balance_amount'))
                ->formatValue(fn ($value) => sprintf(
                    '%0.2f %s',
                    $value,
                    $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                )),
            TextField::new('usedVoucher', $this->translator->trans('pteroca.crud.payment.used_voucher'))
                ->formatValue(fn (?Voucher $value) => $value ? sprintf('%s (%d%%)', $value->getCode(), $value->getValue()) : 'N/A'),
            DateTimeField::new('lastUpdate', $this->translator->trans('pteroca.crud.payment.last_update'))->onlyOnIndex(),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.payment.created_at'))->onlyOnDetail(),
            DateTimeField::new('updatedAt', $this->translator->trans('pteroca.crud.payment.updated_at'))->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $continuePayment = Action::new('continuePayment', $this->translator->trans('pteroca.crud.payment.continue_payment'), 'fa fa-credit-card')
            ->linkToCrudAction('continuePayment')
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(static function (UserPayment $payment) {
                return $payment->getStatus() !== PaymentStatusEnum::PAID->value;
            });
        $showOnlyPaid = Action::new(name: Action::DETAIL, label: $this->translator->trans('pteroca.crud.payment.detail'), icon: 'fa fa-eye')
            ->linkToCrudAction('detail')
            ->displayIf(static function (UserPayment $payment) {
                return $payment->getStatus() === PaymentStatusEnum::PAID->value;
            });

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $continuePayment)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $showOnlyPaid)
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::USER_PAYMENT->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.payment.payment'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.payment.payments'))
            ->setEntityPermission(UserRoleEnum::ROLE_USER->name)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('sessionId')
            ->add('status')
            ->add('amount')
            ->add('currency')
            ->add('createdAt')
            ->add('updatedAt')
        ;
        return parent::configureFilters($filters);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->where('entity.user = :user');
        $queryBuilder->setParameter('user', $this->getUser());

        return $queryBuilder;
    }
}
