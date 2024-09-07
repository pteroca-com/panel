<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\User;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\LogService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Pterodactyl\PterodactylUsernameService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractPanelController
{
    public function __construct(
        LogService $logService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylUsernameService $usernameService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($logService);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            NumberField::new('id')
                ->setDisabled(),
            NumberField::new('pterodactylUserId', $this->translator->trans('pteroca.crud.user.pterodactyl_user_id'))
                ->setDisabled(),
            TextField::new('email', $this->translator->trans('pteroca.crud.user.email')),
            ChoiceField::new('roles', $this->translator->trans('pteroca.crud.user.roles'))
                ->setChoices([
                    'User' => UserRoleEnum::ROLE_USER->value,
                    'Admin' => UserRoleEnum::ROLE_ADMIN->value,
                ])
                ->allowMultipleChoices(),
            NumberField::new('balance', $this->translator->trans('pteroca.crud.user.balance'))
                ->setNumDecimals(2)
                ->setDecimalSeparator('.'),
            TextField::new('plainPassword', $this->translator->trans('pteroca.crud.user.password'))
                ->setFormTypeOption('attr', ['type' => 'password'])
                ->onlyOnForms()
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->setHelp($this->translator->trans('pteroca.crud.user.password_hint')),
            TextField::new('name', $this->translator->trans('pteroca.crud.user.name'))
                ->setMaxLength(255),
            TextField::new('surname', $this->translator->trans('pteroca.crud.user.surname'))
                ->setMaxLength(255),
            BooleanField::new('isVerified', $this->translator->trans('pteroca.crud.user.verified'))
                ->hideOnIndex(),
            BooleanField::new('isBlocked', $this->translator->trans('pteroca.crud.user.blocked'))
                ->hideOnIndex(),
            DateField::new('createdAt', $this->translator->trans('pteroca.crud.user.created_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss')
                ->setDisabled(),
            DateField::new('updatedAt', $this->translator->trans('pteroca.crud.user.updated_at'))
                ->setFormat('dd.MM.yyyy HH:mm:ss')
                ->setDisabled(),
        ];
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
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.user.user'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.user.users'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->value)
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('email')
            ->add('roles')
            ->add('balance')
            ->add('name')
            ->add('surname')
        ;
        return parent::configureFilters($filters);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            if ($plainPassword = $entityInstance->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
                $entityInstance->setPassword($hashedPassword);
            }

            try {
                $createdUser = $this->pterodactylService->getApi()->users->create([
                    'email' => $entityInstance->getEmail(),
                    'username' => $this->usernameService->generateUsername($entityInstance->getEmail()),
                    'first_name' => $entityInstance->getName(),
                    'last_name' => $entityInstance->getSurname(),
                    'password' => $plainPassword,
                ]);
                $entityInstance->setPterodactylUserId($createdUser->id);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            if ($plainPassword = $entityInstance->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
                $entityInstance->setPassword($hashedPassword);
            }

            try {
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
            } catch (\Exception $exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            try {
                $this->pterodactylService->getApi()->users->delete($entityInstance->getPterodactylUserId());
            } catch (\Exception $exception) {
                $this->addFlash('danger', $this->translator->trans('pteroca.system.pterodactyl_error'));
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
