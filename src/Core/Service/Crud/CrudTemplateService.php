<?php

namespace App\Core\Service\Crud;

use App\Core\Enum\OverwriteableCrudTemplatesEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;

class CrudTemplateService
{
    private const TEMPLATES_CACHE_KEY = 'templates_to_override';

    private const DEFAULT_TEMPLATE = 'default';

    public function __construct(
        private readonly SettingService $settingService,
        private readonly CacheInterface $cache,
        private readonly Filesystem $fileSystem,
        private readonly string $projectDirectory,
    )
    {
    }

    public function getTemplatesToOverride(array $templateContext): array
    {
        $templateContext = $this->prepareTemplateContext($templateContext);
        $currentTemplate = $this->settingService->getSetting(SettingEnum::CURRENT_THEME->value);
        return $this->cache->get(
            $this->createTemplateCacheKey($currentTemplate, $templateContext),
            function () use ($currentTemplate, $templateContext) {
                $templatesToOverride = [];

                foreach (OverwriteableCrudTemplatesEnum::toArray() as $template) {
                    $templatePaths = $this->getCrudTemplatePaths(
                        $template,
                        $currentTemplate,
                        $templateContext
                    );

                    if ($this->fileSystem->exists(implode('', $templatePaths))) {
                        $templatesToOverride[$template] = end($templatePaths);
                    } else if ($currentTemplate !== self::DEFAULT_TEMPLATE) {
                        $templatePaths = $this->getCrudTemplatePaths(
                            $template,
                            self::DEFAULT_TEMPLATE,
                            $templateContext
                        );

                        if ($this->fileSystem->exists(implode('', $templatePaths))) {
                            $templatesToOverride[$template] = end($templatePaths);
                        }
                    }
                }

                return $templatesToOverride;
            }
        );
    }

    protected function prepareTemplateContext(array $templateContext): string
    {
        $templateContext = implode('/', $templateContext);
        return str_replace(' ', '_', strtolower($templateContext));
    }

    private function createTemplateCacheKey(string $currentTemplate, string $templateContext): string
    {
        $cacheKey = sprintf('%s_%s_%s', self::TEMPLATES_CACHE_KEY,$currentTemplate, $templateContext);

        return str_replace(['/', '\\'], '_', $cacheKey);
    }

    private function getCrudTemplatePaths(string $template, string $currentTemplate, string $templateContext): array
    {
        $templateName = $this->getCrudTemplateName($template);

        return [
            sprintf(
                '%s/themes/%s/',
                $this->projectDirectory, $currentTemplate,
            ),
            sprintf(
                'panel/crud/%s/%s.html.twig',
                $templateContext, $templateName,
            )
        ];
    }

    private function getCrudTemplateName(string $template): string
    {
        $templateName = explode('/', $template);

        return end($templateName);
    }
}