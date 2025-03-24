<?php

namespace App\Core\Service\Template;

use App\Core\DTO\TemplateOptionsDTO;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;

class TemplateManager
{
    private array $currentTemplateMetadata = [];

    public function __construct(
        private readonly SettingService $settingService,
        private readonly TemplateService $templateService,
    ) {}

    public function getCurrentTemplate(): string
    {
        return $this->settingService->getSetting(SettingEnum::CURRENT_THEME->value);
    }

    public function getCurrentTemplateVersion(): string
    {
        $this->loadCurrentTemplateInfo();

        return $this->currentTemplateMetadata['pterocaVersion'] ?? '0.0.0';
    }

    public function getCurrentTemplateOptions(): TemplateOptionsDTO
    {
        $this->loadCurrentTemplateInfo();

        return new TemplateOptionsDTO(
            $this->currentTemplateMetadata['options']['supportDarkMode'] ?? false,
            $this->currentTemplateMetadata['options']['supportCustomColors'] ?? false,
        );
    }

    public function isTemplateValid(string $template): bool
    {
        return !empty($this->templateService->getTemplateInfo($template));
    }

    private function loadCurrentTemplateInfo(): void
    {
        if (!empty($this->currentTemplateInfo)) {
            return;
        }

        $currentTemplate = $this->getCurrentTemplate();
        $this->currentTemplateMetadata = $this->templateService->getRawTemplateInfo($currentTemplate);
    }
}
