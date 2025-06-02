<?php

namespace App\Core\Controller;

use App\Core\Contract\UserInterface;
use App\Core\Controller\Panel\AbstractPanelController;
use App\Core\Entity\Panel\UserAccount;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAccountCrudController extends AbstractPanelController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TranslatorInterface $translator,
        private readonly PterodactylService $pterodactylService,
        PanelCrudService $panelCrudService,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return UserAccount::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->disable(Action::NEW, Action::DELETE, Action::DETAIL);
        $actions->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);

        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        $uploadDirectory = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $this->getParameter('avatar_directory'),
        );

        return [
            FormField::addRow(),
            IdField::new('id')->hideOnForm(),
            EmailField::new('email', $this->translator->trans('pteroca.crud.user.email'))
                ->setFormTypeOption('disabled', true)
                ->setColumns(5),
            ImageField::new('avatarPath', $this->translator->trans('pteroca.crud.user.avatar'))
                ->setBasePath($this->getParameter('avatar_base_path'))
                ->setUploadDir($uploadDirectory)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false)
                ->setFileConstraints(new Image([
                    'maxSize' => $this->getParameter('avatar_max_size'),
                    'mimeTypes' => $this->getParameter('avatar_allowed_extensions'),
                ]))
                ->setColumns(5),

            FormField::addRow(),
            TextField::new('name', $this->translator->trans('pteroca.crud.user.name'))
                ->setMaxLength(255)
                ->setColumns(5),
            TextField::new('surname', $this->translator->trans('pteroca.crud.user.surname'))
                ->setMaxLength(255)
                ->setColumns(5),

            FormField::addRow(),
            TextField::new('plainPassword', $this->translator->trans('pteroca.crud.user.password'))
                ->setFormTypeOption('attr', ['type' => 'password'])
                ->onlyOnForms()
                ->setHelp($this->translator->trans('pteroca.crud.user.password_hint'))
                ->setColumns(5),
            TextField::new('repeatPassword', $this->translator->trans('pteroca.crud.user.repeat_password'))
                ->setFormTypeOption('attr', ['type' => 'password'])
                ->onlyOnForms()
                ->setHelp($this->translator->trans('pteroca.crud.user.repeat_password_hint'))
                ->setColumns(5),
            
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::USER_ACCOUNT->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.dashboard.account_settings'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.dashboard.account_settings'))
            ->setEntityPermission(UserRoleEnum::ROLE_USER->name);

        return parent::configureCrud($crud);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->where('entity.email = :email');
        $queryBuilder->setParameter('email', $this->getUser()->getEmail());

        return $queryBuilder;
    }

    public function index(AdminContext $context)
    {
        $user = $this->getUser();

        if (empty($user)) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('panel', [
            'crudAction' => 'edit',
            'crudControllerFqcn' => UserAccountCrudController::class,
            'entityId' => $this->getUser()->getId(),
        ]);
    }

    public function edit(AdminContext $context)
    {
        return $this->validateAccount($context);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof UserAccount) {
            // Sprawdź, czy hasła się zgadzają
            if ($entityInstance->getPlainPassword() && $entityInstance->getRepeatPassword()) {
                if ($entityInstance->getPlainPassword() !== $entityInstance->getRepeatPassword()) {
                    throw new \InvalidArgumentException($this->translator->trans('pteroca.crud.user.passwords_must_match'));
                }
            }

            if ($plainPassword = $entityInstance->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
                $entityInstance->setPassword($hashedPassword);
            }

            $pterodactylAccount = $this->pterodactylService
                ->getApi()
                ->users
                ->get($entityInstance->getPterodactylUserId());
            if (!empty($pterodactylAccount->username)) {
                $pterodactylAccountDetails = [
                    'username' => $pterodactylAccount->username,
                    'email' => $entityInstance->getEmail(),
                    'first_name' => $entityInstance->getName(),
                    'last_name' => $entityInstance->getSurname(),
                ];
                if ($plainPassword) {
                    $pterodactylAccountDetails['password'] = $plainPassword;
                }
                $this->pterodactylService->getApi()->users->update(
                    $entityInstance->getPterodactylUserId(),
                    $pterodactylAccountDetails
                );
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function validateAccount(AdminContext $context)
    {
        $user = $this->getUser();
        if (empty($user)) {
            return $this->redirectToRoute('app_login');
        }

        $entityId = $context->getRequest()->get('entityId');
        if (empty($entityId) || !is_numeric($entityId) || $user->getId() !== (int)$entityId) {
            return $this->redirectToRoute('panel', [
                'crudAction' => 'edit',
                'crudControllerFqcn' => UserAccountCrudController::class,
                'entityId' => $user->getId(),
            ]);
        }

        return parent::edit($context);
    }
}
