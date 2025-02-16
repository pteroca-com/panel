<?php

namespace App\Core\Twig;

use App\Core\Service\Template\TemplateManager;
use Twig\Loader\FilesystemLoader;

class DynamicTwigLoader extends FilesystemLoader
{
    private const DEFAULT_TEMPLATE = 'default';

    public function __construct(
        private readonly TemplateManager $templateManager,
        string $templatesBaseDir,
    )
    {
        parent::__construct();

        $currentTemplate = $this->templateManager->getCurrentTemplate();
        $templateToLoad = $this->templateManager->isTemplateValid($currentTemplate)
            ? $currentTemplate
            : self::DEFAULT_TEMPLATE;

        $this->prependPath("$templatesBaseDir/$templateToLoad");

        if (file_exists("$templatesBaseDir/$templateToLoad/bundles/EasyAdminBundle")) {
            $this->prependPath("$templatesBaseDir/$templateToLoad/bundles/EasyAdminBundle", 'EasyAdmin');
        }
    }
}
