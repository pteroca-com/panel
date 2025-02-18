<?php

namespace App\Core\Service\Template;

use App\Core\Service\System\SystemVersionService;
use DirectoryIterator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateService
{
    public const METADATA_FILE = 'template.json';

    private const TEMPLATES_DIRECTORY = 'themes';

    public function __construct(
        private readonly SystemVersionService $systemVersionService,
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
        $templateInfo = $this->loadTemplateMetadata($this->getTemplatePath($templateName));

        $currentPterocaVersion = $this->systemVersionService->getCurrentVersion();
        $pterocaVersionIndex = $this->translator->trans('pteroca.crud.setting.template.pterocaVersion');
        $templatePterocaVersion = $templateInfo[$pterocaVersionIndex] ?? null;
        $isOutdated = !empty($templatePterocaVersion)
            && version_compare($templatePterocaVersion, $currentPterocaVersion, '<');

        if ($isOutdated) {
            $templateInfo[$pterocaVersionIndex] = sprintf(
                '%s (%s %s)',
                $templatePterocaVersion,
                '<i class="fas fa-exclamation-triangle text-warning"></i>',
                $this->translator->trans('pteroca.crud.setting.template.outdated')
            );
        }

        return $templateInfo;
    }

    public function getTemplatePath(?string $templateName = null): string
    {
        $templatePath = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . self::TEMPLATES_DIRECTORY;
        if ($templateName !== null) {
            $templatePath .= DIRECTORY_SEPARATOR . $templateName;
        }

        return $templatePath;
    }

    public function getTemplateAssetsPath(?string $templateName): string
    {
        $assetsPath = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'theme';
        if ($templateName !== null) {
            $assetsPath .= DIRECTORY_SEPARATOR . $templateName;
        }

        return $assetsPath;
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
}
