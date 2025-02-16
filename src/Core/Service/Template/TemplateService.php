<?php

namespace App\Core\Service\Template;

use DirectoryIterator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateService
{
    private const TEMPLATES_DIRECTORY = 'templates';

    private const METADATA_FILE = 'template.json';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getAvailableTemplates(): array
    {
        $templates = [];

        $templatesDirectoryPath = $this->getTemplatePath();
        $directory = new DirectoryIterator($templatesDirectoryPath);
        foreach ($directory as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $templateMetadata = $this->loadTemplateMetadata($fileInfo->getPathname());
                if (empty($templateMetadata)) {
                    continue;
                }

                $templateName = current($templateMetadata);
                $templates[$templateName] = $templateName;
            }
        }

        return $templates;
    }

    public function getTemplateInfo(string $templateName): array
    {
        return $this->loadTemplateMetadata($this->getTemplatePath($templateName));
    }

    private function loadTemplateMetadata(string $templatePath): array
    {
        $metadataPath = $templatePath . DIRECTORY_SEPARATOR . self::METADATA_FILE;
        if (!file_exists($metadataPath)) {
            return [];
        }

        $metaData = json_decode(file_get_contents($metadataPath), true);
        if (empty($metaData['template'])) {
            return [];
        }

        $preparedMetaData = [];
        foreach ($metaData['template'] as $key => $value) {
            $label = $this->translator->trans(sprintf('pteroca.crud.setting.template.%s', $key));

            if (!empty($label)) {
                $preparedMetaData[$label] = $value;
            }
        }

        return $preparedMetaData;
    }

    private function getTemplatePath(?string $templateName = null): string
    {
        $templatePath = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . self::TEMPLATES_DIRECTORY;
        if ($templateName !== null) {
            $templatePath .= DIRECTORY_SEPARATOR . $templateName;
        }

        return $templatePath;
    }
}
