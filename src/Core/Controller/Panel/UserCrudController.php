<?php

namespace App\Core\Controller\Panel;

use Exception;
use App\Core\Entity\User;
use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\LogActionEnum;
use App\Core\Contract\UserInterface;
use App\Core\Service\Logs\LogService;
use App\Core\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use App\Core\Enum\CrudTemplateContextEnum;
use App\Core\Service\Crud\PanelCrudService;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use App\Core\Exception\PterodactylUserNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserCrudController extends AbstractPanelController
{
    public function __construct(
        PanelCrudService $panelCrudService,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
        private readonly LogService $logService,
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
            $fields[] = DateField::new('deletedAt', $this->translator->trans('pteroca.crud.user.deleted_at'))
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
            $fields[] = DateField::new('deletedAt', $this->translator->trans('pteroca.crud.user.deleted_at'))
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
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn (Action $action) => $action->displayIf(fn ($entity) => $entity instanceof UserInterface && !$entity->isDeleted()))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->displayIf(fn ($entity) => $entity instanceof UserInterface && !$entity->isDeleted()));
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
            ->add('deletedAt')
        ;
        return parent::configureFilters($filters);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof UserInterface) {
            try {
                $result = $this->userService->createOrRestoreUser($entityInstance, $entityInstance->getPlainPassword());

                if ($result['action'] === 'restored') {
                    $this->addFlash('success', $this->translator->trans('pteroca.crud.user.account_restored'));
                    $entityInstance = $result['user'];
                } else {
                    $this->addFlash('success', $this->translator->trans('pteroca.crud.user.created_successfully'));
                }
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
            $activeServersCount = $entityManager->getRepository(Server::class)->count([
                'user' => $entityInstance,
                'deletedAt' => null
            ]);

            if ($activeServersCount > 0) {
                $this->addFlash('danger', $this->translator->trans('pteroca.crud.user.cannot_delete_user_with_active_servers', ['count' => $activeServersCount]));
                return;
            }

            $pterodactylUserNotFound = false;

            try {
                $this->userService->deleteUserFromPterodactyl($entityInstance);
            } catch (PterodactylUserNotFoundException) {
                $pterodactylUserNotFound = true;
            } catch (Exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
                return;
            }

            $entityInstance->softDelete();
            $entityManager->persist($entityInstance);
            $entityManager->flush();

            $this->logService->logAction($entityInstance, LogActionEnum::ENTITY_DELETE, [
                'deleted_by' => $this->getUser()->getEmail(),
            ]);

            if ($pterodactylUserNotFound) {
                $this->addFlash('warning', $this->translator->trans('pteroca.crud.user.pterodactyl_user_not_found'));
            } else {
                $this->addFlash('success', $this->translator->trans('pteroca.crud.user.deleted_successfully'));
            }

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
