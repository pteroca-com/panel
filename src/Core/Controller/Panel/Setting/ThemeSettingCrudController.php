<?php

namespace App\Core\Controller\Panel\Setting;

use App\Core\Enum\SettingContextEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\SettingRepository;
use App\Core\Service\LocaleService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\SettingService;
use App\Core\Service\System\TemplateService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeSettingCrudController extends AbstractSettingCrudController
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly TranslatorInterface $translator,
        LogService $logService,
        RequestStack $requestStack,
        SettingRepository $settingRepository,
        SettingService $settingService,
        LocaleService $localeService,
    )
    {
        parent::__construct($logService, $requestStack, $translator, $settingRepository, $settingService, $localeService);
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
            if ($this->currentEntity->getName() === SettingEnum::CURRENT_THEME->value) {
                $valueFieldIndex = $this->findValueFieldIndexByName($fields);
                $fields[$valueFieldIndex] = ChoiceField::new('value', $this->translator->trans('pteroca.crud.setting.value'))
                    ->setChoices($this->templateService->getAvailableTemplates())
                    ->setRequired(true);
            }
        }

        return $fields;
    }

    public function configureAssets(Assets $assets): Assets
    {
        $assets = parent::configureAssets($assets);

        if (!empty($this->currentEntity) && $this->currentEntity->getName() === SettingEnum::CURRENT_THEME->value) {
            $assets->addJsFile('assets/js/templateSetting.js');
        }

        return $assets;
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
