<?php

namespace App\Core\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CoreExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container)
    {
        // Add plugin translation paths to framework translation configuration
        $this->prependPluginTranslations($container);
    }

    private function prependPluginTranslations(ContainerBuilder $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        $pluginsDir = $projectDir . '/plugins';

        if (!is_dir($pluginsDir)) {
            return;
        }

        // Scan for plugins with 'ui' capability
        $directories = scandir($pluginsDir);
        if ($directories === false) {
            return;
        }

        $translationPaths = [];

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $pluginPath = $pluginsDir . '/' . $dir;
            if (!is_dir($pluginPath)) {
                continue;
            }

            $manifestPath = $pluginPath . '/plugin.json';
            if (!file_exists($manifestPath)) {
                continue;
            }

            $manifestContent = file_get_contents($manifestPath);
            if ($manifestContent === false) {
                continue;
            }

            $manifest = json_decode($manifestContent, true);
            if (!$manifest || json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $capabilities = $manifest['capabilities'] ?? [];
            if (!in_array('ui', $capabilities, true)) {
                continue;
            }

            $translationsPath = $pluginPath . '/translations';
            if (is_dir($translationsPath)) {
                $translationPaths[] = $translationsPath;
            }
        }

        if (!empty($translationPaths)) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => $translationPaths,
                ],
            ]);
        }
    }
}