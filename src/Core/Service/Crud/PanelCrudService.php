<?php

namespace App\Core\Service\Crud;

use App\Core\Entity\Setting;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\OverwriteableCrudTemplatesEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Service\Logs\LogService;
use App\Core\Service\SettingService;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PanelCrudService
{
    private Serializer $serializer;

    private const TEMPLATES_CACHE_KEY = 'templates_to_override';

    public function __construct(
        private readonly SettingService $settingService,
        private readonly LogService $logService,
        private readonly FileSystem $fileSystem,
        private readonly CacheInterface $cache,
        private readonly string $projectDirectory,
    ) {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function logEntityAction(LogActionEnum $action, $entityInstance, User $user, string $entityName): void
    {
        if (is_a($entityInstance, Setting::class)
            && $entityInstance->getType() === SettingTypeEnum::SECRET->value) {
            $entityInstance->setValue('********');
        }
        $this->logService->logAction(
            $user,
            $action,
            [
                'entityName' => $entityName,
                'entity' => $this->serializer->normalize($entityInstance, null, ['groups' => 'log'])
            ],
        );
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