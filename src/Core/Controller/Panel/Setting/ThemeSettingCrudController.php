<?php

namespace App\Core\Controller\Panel\Setting;

use App\Core\DTO\TemplateOptionsDTO;
use App\Core\Enum\SettingContextEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\SettingRepository;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Service\LocaleService;
use App\Core\Service\SettingService;
use App\Core\Service\Template\TemplateManager;
use App\Core\Service\Template\TemplateService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ColorScheme;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeSettingCrudController extends AbstractSettingCrudController
{
    private TemplateOptionsDTO $currentTemplateOptions;

    private bool $disableDarkMode;

    public function __construct(
        PanelCrudService $panelCrudService,
        RequestStack $requestStack,
        SettingRepository $settingRepository,
        SettingService $settingService,
        LocaleService $localeService,
        private readonly TemplateService $templateService,
        private readonly TemplateManager $templateManager,
        private readonly TranslatorInterface $translator,
    )
    {
        parent::__construct(
            $panelCrudService,
            $requestStack,
            $translator,
            $settingRepository,
            $settingService,
            $localeService
        );

        $this->currentTemplateOptions = $this->templateManager->getCurrentTemplateOptions();
        $this->disableDarkMode = !$this->currentTemplateOptions->isSupportDarkMode()
            || $settingService->getSetting(SettingEnum::THEME_DISABLE_DARK_MODE->value);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $this->context = SettingContextEnum::THEME;

        return parent::configureCrud($crud);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = parent::configureFields($pageName);

        if ($pageName === Crud::PAGE_EDIT) {
            switch ($this->currentEntity->getName()) {
                case SettingEnum::CURRENT_THEME->value:
                    $valueFieldIndex = $this->findValueFieldIndexByName($fields);
                    $fields[$valueFieldIndex] = ChoiceField::new('value', $this->translator->trans('pteroca.crud.setting.value'))
                        ->setChoices($this->templateService->getAvailableTemplates())
                        ->setRequired(true);
                    break;
                case SettingEnum::THEME_DEFAULT_MODE->value:
                    $valueFieldIndex = $this->findValueFieldIndexByName($fields);
                    $fields[$valueFieldIndex] = ChoiceField::new('value', $this->translator->trans('pteroca.crud.setting.value'))
                        ->setChoices([
                            'Light' => ColorScheme::LIGHT,
                            'Dark' => ColorScheme::DARK,
                            'Auto' => ColorScheme::AUTO,
                        ])
                        ->setRequired(true);
                    break;
                case SettingEnum::SIDEBAR_STYLE->value:
                    $valueFieldIndex = $this->findValueFieldIndexByName($fields);
                    $fields[$valueFieldIndex] = ChoiceField::new('value', $this->translator->trans('pteroca.crud.setting.value'))
                        ->setChoices([
                            // Current implementation in use
                            'Current' => 'current',
                            // Placeholder for upcoming redesign
                            'Younglin (preview)' => 'younglin',
                        ])
                        ->setRequired(true);
                    break;
            }
        }

        return $fields;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $hiddenSettings = [];

        if (!$this->currentTemplateOptions->isSupportDarkMode()) {
            $hiddenSettings[] = SettingEnum::THEME_DISABLE_DARK_MODE->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_PRIMARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_SECONDARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_BACKGROUND_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_LINK_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_LINK_HOVER_COLOR->value;
        }

        if (!$this->currentTemplateOptions->isSupportCustomColors()) {
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_PRIMARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_SECONDARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_BACKGROUND_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_LINK_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_LINK_HOVER_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_PRIMARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_SECONDARY_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_BACKGROUND_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_LINK_COLOR->value;
            $hiddenSettings[] = SettingEnum::DEFAULT_THEME_DARK_LINK_HOVER_COLOR->value;
        }

        if ($this->disableDarkMode) {
            $hiddenSettings[] = SettingEnum::THEME_DEFAULT_MODE->value;
        }

        $hiddenSettings = array_unique($hiddenSettings);
        if (!empty($hiddenSettings)) {
            $qb->andWhere('entity.name NOT IN (:hiddenSettings)')
                ->setParameter('hiddenSettings', $hiddenSettings);
        }

        return $qb;
    }

    private function findValueFieldIndexByName(iterable $fields): ?int
    {
        foreach ($fields as $key => $field) {
            if ($field->getAsDto()->getProperty() === 'value') {
                return $key;
            }
        }

        return null;
    }
}
