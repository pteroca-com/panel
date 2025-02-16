<?php

namespace App\Core\Service\Template;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

class TemplateManager
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly TemplateService $templateService,
    ) {}

    public function getCurrentTemplate(): string
    {
        return $this->settingService->getSetting(SettingEnum::CURRENT_THEME->value);
    }

    public function isTemplateValid(string $template): bool
    {
        return !empty($this->templateService->getTemplateInfo($template));
    }
}
