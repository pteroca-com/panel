<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Setting;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\SettingRepository;
use App\Core\Service\LocaleService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingCrudController extends AbstractPanelController
{
    public function __construct(
        LogService $logService,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly SettingRepository $settingRepository,
        private readonly SettingService $settingService,
        private readonly LocaleService $localeService,
    ) {
        parent::__construct($logService);
    }

    public static function getEntityFqcn(): string
    {
        return Setting::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getSettingEntity();
        $fields = [
            TextField::new('name', $this->translator->trans('pteroca.crud.setting.name'))
                ->setDisabled($pageName === Crud::PAGE_EDIT)
                ->formatValue(function ($value) {
                    $hintIndex = "pteroca.crud.setting.hints.$value";
                    $hint = $this->translator->trans($hintIndex);
                    if ($hint !== $hintIndex) {
                        return sprintf("%s<br><small>(%s)</small>", $value, $hint);
                    }
                    return $value;
                }),
            ChoiceField::new('type', $this->translator->trans('pteroca.crud.setting.type'))
                ->setChoices(SettingTypeEnum::getValues())
                ->onlyWhenCreating(),
        ];

        $valueLabel = $this->translator->trans('pteroca.crud.setting.value');
        $valueField = match ($entity?->getType()) {
            SettingTypeEnum::COLOR->value => TextField::new('value', $valueLabel)
                ->setFormType(ColorType::class),
            SettingTypeEnum::BOOLEAN->value => ChoiceField::new('value', $valueLabel)
                ->setChoices([
                    $this->translator->trans('pteroca.crud.setting.yes') => '1',
                    $this->translator->trans('pteroca.crud.setting.no') => '0',
                ])
                ->formatValue(fn ($value, $entity) => $value ? '1' : '0'),
            SettingTypeEnum::NUMBER->value => NumberField::new('value', $valueLabel),
            SettingTypeEnum::TEXT->value => TextField::new('value', $valueLabel),
            SettingTypeEnum::TWIG->value => CodeEditorField::new('value', $valueLabel)
                ->setLanguage('twig')
                ->setNumOfRows(20),
            SettingTypeEnum::LOCALE->value => ChoiceField::new('value', $valueLabel)
                ->setChoices($this->localeService->getAvailableLocales()),
            SettingTypeEnum::URL->value => UrlField::new('value', $valueLabel),
            SettingTypeEnum::EMAIL->value => EmailField::new('value', $valueLabel),
            SettingTypeEnum::IMAGE->value => ImageField::new('value', $valueLabel)
                ->setUploadDir('public/uploads/settings')
                ->setBasePath('/uploads/settings')
                ->setUploadedFileNamePattern('[randomhash].[extension]'),
            default => TextareaField::new('value', $valueLabel)
                ->formatValue(function ($value, $entity) {
                    return match ($entity->getType()) {
                        SettingTypeEnum::SECRET->value => '********',
                        SettingTypeEnum::BOOLEAN->value => $value
                            ? $this->translator->trans('pteroca.crud.setting.yes')
                            : $this->translator->trans('pteroca.crud.setting.no'),
                        default => $value,
                    };
                }),
        };

        $valueField->setRequired(true);
        $fields[] = $valueField;

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.setting.add')))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.setting.add')))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel($this->translator->trans('pteroca.crud.setting.save')))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
        if (strtolower($_ENV['APP_ENV']) === 'prod') {
            $actions
                ->remove(Crud::PAGE_INDEX, Action::DELETE)
                ->remove(Crud::PAGE_INDEX, Action::NEW);
        }
        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('pteroca.crud.setting.setting'))
            ->setEntityLabelInPlural($this->translator->trans('pteroca.crud.setting.settings'))
            ->setEntityPermission(UserRoleEnum::ROLE_ADMIN->name);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('name')
            ->add('value')
        ;
        return parent::configureFilters($filters);
    }

    private function getSettingEntity(): ?Setting
    {
        $request = $this->requestStack->getCurrentRequest();
        $crudAction = $request->query->get('crudAction');
        if ($crudAction === 'index') {
            return null;
        }

        $id = $request->query->get('entityId');
        if ($id) {
            return $this->settingRepository->find($id);
        }
        return null;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->settingService->saveSettingInCache($entityInstance->getName(), $entityInstance->getValue());
        $this->settingRepository->save($entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->settingService->saveSettingInCache($entityInstance->getName(), $entityInstance->getValue());
        $this->settingRepository->save($entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->settingService->deleteSettingFromCache($entityInstance->getName());
        parent::deleteEntity($entityManager, $entityInstance);
    }
}
