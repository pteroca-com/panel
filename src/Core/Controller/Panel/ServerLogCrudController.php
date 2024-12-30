<?php


namespace App\Core\Controller\Panel;

use App\Core\Entity\ServerLog;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Logs\LogService;
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

class ServerLogCrudController extends AbstractPanelController
{
    public function __construct(
        LogService $logService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($logService);
    }

    public static function getEntityFqcn(): string
    {
        return ServerLog::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('actionId', $this->translator->trans('pteroca.crud.log.action'))
                ->setDisabled()
            ->formatValue(fn ($value) => $this->translator->trans('pteroca.server_actions.' . $value)),
            CodeEditorField::new('details', $this->translator->trans('pteroca.crud.log.details'))
                ->setDisabled()
                ->formatValue(fn ($value) => $value === '[]' ? '' : json_encode(json_decode($value), JSON_PRETTY_PRINT)),
            DateTimeField::new('createdAt', $this->translator->trans('pteroca.crud.log.created_at'))
                ->setDisabled(),
            AssociationField::new('user', $this->translator->trans('pteroca.crud.log.user'))
                ->setDisabled(),
            AssociationField::new('server', $this->translator->trans('pteroca.crud.log.server'))
                ->setDisabled(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.log.server_log'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.log.server_logs'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('actionId')
            ->add('details')
            ->add('createdAt')
            ->add('user')
            ->add('server');
        return parent::configureFilters($filters);
    }
}
