<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\EmailLog;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailLogCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('emailType', $this->translator->trans('pteroca.crud.email_log.email_type'))
                ->setDisabled()
                ->formatValue(fn ($value) => $this->translator->trans('pteroca.email_types.' . strtolower($value))),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.email_log.user'))
                ->setDisabled(),
            AssociationField::new('server', $this->translator->trans('pteroca.crud.email_log.server'))
                ->setDisabled(),
            CodeEditorField::new('metadata', $this->translator->trans('pteroca.crud.email_log.metadata'))
                ->setDisabled()
                ->formatValue(fn ($value) => $value === '[]' || empty($value) ? '' : json_encode(json_decode($value), JSON_PRETTY_PRINT)),
            DateTimeField::new('sentAt', $this->translator->trans('pteroca.crud.email_log.sent_at'))
                ->setDisabled(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT, Action::DELETE, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::EMAIL_LOG->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.email_log.email_log'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.email_log.email_logs'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['sentAt' => 'DESC'])
            ->showEntityActionsInlined();

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('emailType')
            ->add('user')
            ->add('server')
            ->add('metadata')
            ->add('sentAt');

        return parent::configureFilters($filters);
    }
}
