<?php

namespace App\Core\Controller;

use App\Core\Entity\User;
use App\Core\Entity\UserAccount;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Pterodactyl\PterodactylService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAccountCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TranslatorInterface $translator,
        private readonly PterodactylService $pterodactylService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return UserAccount::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->disable(Action::NEW);
        $actions->disable(Action::DELETE);
        $actions->disable(Action::DETAIL);
        $actions->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email', $this->translator->trans('pteroca.crud.user.email'))
                ->setFormTypeOption('disabled', true),
            TextField::new('name', $this->translator->trans('pteroca.crud.user.name'))
                ->setMaxLength(255),
            TextField::new('surname', $this->translator->trans('pteroca.crud.user.surname'))
                ->setMaxLength(255),
            TextField::new('plainPassword', $this->translator->trans('pteroca.crud.user.password'))
                ->setFormTypeOption('attr', ['type' => 'password'])
                ->onlyOnForms()
                ->setHelp($this->translator->trans('pteroca.crud.user.password_hint')),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.dashboard.account_settings'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.dashboard.account_settings'))
            ->setEntityPermission(UserRoleEnum::ROLE_USER->name);
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
        if ($entityInstance instanceof User) {
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