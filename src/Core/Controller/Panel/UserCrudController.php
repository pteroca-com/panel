<?php

namespace App\Core\Controller\Panel;

use App\Core\Contract\UserInterface;
use App\Core\Entity\User;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($panelCrudService);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $uploadDirectory = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $this->getParameter('avatar_directory'),
        );

        $fields = [];

        if ($pageName !== Crud::PAGE_NEW) {
            $fields[] = NumberField::new('id')
                ->setDisabled()
                ->setColumns(2);
            $fields[] = NumberField::new('pterodactylUserId', $this->translator->trans('pteroca.crud.user.pterodactyl_user_id'))
                ->setDisabled()
                ->setColumns(2);
            $fields[] = DateField::new('createdAt', $this->translator->trans('pteroca.crud.user.created_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss')
                ->setDisabled()
                ->setColumns(2)
                ->hideOnIndex();
            $fields[] = DateField::new('updatedAt', $this->translator->trans('pteroca.crud.user.updated_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss')
                ->setDisabled()
                ->setColumns(2)
                ->hideOnIndex();
            $fields[] = FormField::addRow();
        }

        $fields = array_merge($fields, [
            ImageField::new('avatarPath', $this->translator->trans('pteroca.crud.user.avatar'))
                ->setBasePath($this->getParameter('avatar_base_path'))
                ->setUploadDir($uploadDirectory)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false)
                ->setColumns(4),
            ChoiceField::new('roles', $this->translator->trans('pteroca.crud.user.roles'))
                ->setChoices([
                    'User' => UserRoleEnum::ROLE_USER->name,
                    'Admin' => UserRoleEnum::ROLE_ADMIN->name,
                ])
                ->allowMultipleChoices(),
            FormField::addRow(),
            TextField::new('email', $this->translator->trans('pteroca.crud.user.email'))
                ->setColumns(6),
            NumberField::new('balance', $this->translator->trans('pteroca.crud.user.balance'))
                ->setNumDecimals(2)
                ->setDecimalSeparator('.'),
            FormField::addRow(),
            TextField::new('name', $this->translator->trans('pteroca.crud.user.name'))
                ->setMaxLength(255)
                ->setColumns(4),
            TextField::new('surname', $this->translator->trans('pteroca.crud.user.surname'))
                ->setMaxLength(255)
                ->setColumns(4),
            FormField::addRow(),
            BooleanField::new('isVerified', $this->translator->trans('pteroca.crud.user.verified'))
                ->hideOnIndex()
                ->setColumns(2),
            BooleanField::new('isBlocked', $this->translator->trans('pteroca.crud.user.blocked'))
                ->hideOnIndex()
                ->setColumns(2),
            FormField::addRow(),
            TextField::new('plainPassword', $this->translator->trans('pteroca.crud.user.password'))
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => $this->translator->trans('pteroca.crud.user.password'),
                        'attr' => ['style' => 'max-width: 400px;'],
                        'help' => $pageName === Crud::PAGE_EDIT ? $this->translator->trans('pteroca.crud.user.password_hint') : null,
                    ],
                    'second_options' => [
                        'label' => $this->translator->trans('pteroca.crud.user.repeat_password'),
                        'attr' => ['style' => 'max-width: 400px;'],
                    ],
                    'invalid_message' => $this->translator->trans('pteroca.crud.user.passwords_must_match'),
                ])
                ->onlyOnForms()
                ->setRequired($pageName === Crud::PAGE_NEW),
                FormField::addRow(),
        ]);

        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = DateField::new('createdAt', $this->translator->trans('pteroca.crud.user.created_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss');
            $fields[] = DateField::new('updatedAt', $this->translator->trans('pteroca.crud.user.updated_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss');
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.user.add')))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.user.add')))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.user.save')))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->appendCrudTemplateContext(CrudTemplateContextEnum::USER->value);

        $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.user.user'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.user.users'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name)
            ->setDefaultSort(['createdAt' => 'DESC']);

        return parent::configureCrud($crud);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('email')
            ->add('roles')
            ->add('balance')
            ->add('name')
            ->add('surname')
            ->add('pterodactylUserId')
            ->add('isVerified')
            ->add('isBlocked')
            ->add('createdAt')
            ->add('updatedAt')
        ;
        return parent::configureFilters($filters);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof UserInterface) {
            try {
                $this->userService->createUserWithPterodactylAccount($entityInstance, $entityInstance->getPlainPassword());
            } catch (Exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
                return;
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof UserInterface) {
            try {
                $this->userService->updateUserInPterodactyl($entityInstance, $entityInstance->getPlainPassword());
            } catch (Exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof UserInterface) {
            try {
                $this->userService->deleteUserFromPterodactyl($entityInstance);
            } catch (Exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
