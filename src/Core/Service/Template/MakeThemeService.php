<?php

namespace App\Core\Service\Template;

use Symfony\Component\Filesystem\Filesystem;

class MakeThemeService
{
    private const DEFAULT_THEME = 'default';

    public function __construct(
        private readonly TemplateService $templateService,
        private readonly Filesystem $filesystem,
    ) {}

    public function createTheme(array $metadata): void
    {
        $preparedTemplateName = str_replace(' ', '_', strtolower($metadata['template']['name']));
        $newTemplatePath = $this->templateService->getTemplatePath($preparedTemplateName);

       $this->copyThemeFiles($newTemplatePath, $metadata);
       $this->copyThemeAssets($preparedTemplateName);
    }

    private function copyThemeFiles(string $newTemplatePath, array $metadata): void
    {
        if ($this->filesystem->exists($newTemplatePath)) {
            throw new \RuntimeException(sprintf('Theme "%s" already exists', $metadata['template']['name']));
        }

        $this->filesystem->mirror(
            $this->templateService->getTemplatePath(self::DEFAULT_THEME),
            $newTemplatePath
        );

        $metadataFile = $newTemplatePath . '/' . TemplateService::METADATA_FILE;
        $this->filesystem->dumpFile($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    private function copyThemeAssets(string $templateName): void
    {
        $assetsPath = $this->templateService->getTemplateAssetsPath($templateName);
        if ($this->filesystem->exists($assetsPath)) {
            return;
        }

        $this->filesystem->mirror(
            $this->templateService->getTemplateAssetsPath(self::DEFAULT_THEME),
            $assetsPath
        );
    }
}
