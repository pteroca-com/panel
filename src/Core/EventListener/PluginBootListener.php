<?php

declare(strict_types=1);

namespace App\Core\EventListener;

use App\Core\Repository\PluginRepository;
use App\Core\Service\Plugin\PluginAutoloader;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Event listener that bootstraps all ENABLED plugins on kernel boot.
 *
 * Performs runtime registration of plugin components:
 * - PSR-4 autoloaders for plugin classes
 * - Twig namespaces for plugin templates
 * - Translation resources for plugin translations
 *
 * This runs once per request with high priority (before routing) to ensure
 * plugin resources are available when needed.
 */
class PluginBootListener implements EventSubscriberInterface
{
    private static bool $booted = false;

    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly PluginAutoloader $pluginAutoloader,
        private readonly FilesystemLoader $twigLoader,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Register on kernel.request with high priority (before routing)
            KernelEvents::REQUEST => ['onKernelRequest', 512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only boot once per PHP process
        if (self::$booted) {
            return;
        }

        // Only run on main request (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            // Find all ENABLED plugins
            $enabledPlugins = $this->pluginRepository->findEnabled();

            // Register autoloader for each enabled plugin
            foreach ($enabledPlugins as $plugin) {
                $registered = $this->pluginAutoloader->registerPlugin($plugin);

                if ($registered) {
                    $this->logger->debug("Registered autoloader for plugin: {$plugin->getName()}");
                }
            }

            // Register Twig namespaces for enabled plugins
            $this->registerTwigNamespaces($enabledPlugins);

            // Register translation resources for enabled plugins
            $this->registerTranslations($enabledPlugins);

            self::$booted = true;

        } catch (\Exception $e) {
            // Silently fail during boot (e.g., database not available yet)
            $this->logger->warning("Failed to boot plugins: {$e->getMessage()}");
        }
    }

    /**
     * Register Twig namespaces for enabled plugins with 'ui' capability.
     *
     * Each plugin gets a Twig namespace like @PluginHelloWorld/ pointing to
     * /plugins/hello-world/templates/
     *
     * @param array $plugins Array of Plugin entities
     */
    private function registerTwigNamespaces(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            // Only register for plugins with 'ui' capability
            if (!$plugin->hasCapability('ui')) {
                continue;
            }

            $templatePath = sprintf(
                '%s/plugins/%s/templates',
                $this->projectDir,
                $plugin->getName()
            );

            // Skip if templates directory doesn't exist
            if (!is_dir($templatePath)) {
                continue;
            }

            $namespace = $this->getPluginTwigNamespace($plugin->getName());

            try {
                $this->twigLoader->addPath($templatePath, $namespace);

                $this->logger->debug("Registered Twig namespace for plugin", [
                    'plugin' => $plugin->getName(),
                    'namespace' => $namespace,
                    'path' => $templatePath,
                ]);
            } catch (\Exception $e) {
                $this->logger->error("Failed to register Twig namespace", [
                    'plugin' => $plugin->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Register translation resources for enabled plugins with 'ui' capability.
     *
     * Scans /plugins/{plugin-name}/translations/ for messages.{locale}.yaml files
     * and registers them in translation domain: plugin_{plugin_name}
     *
     * @param array $plugins Array of Plugin entities
     */
    private function registerTranslations(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            // Only register for plugins with 'ui' capability
            if (!$plugin->hasCapability('ui')) {
                continue;
            }

            $translationsPath = sprintf(
                '%s/plugins/%s/translations',
                $this->projectDir,
                $plugin->getName()
            );

            // Skip if translations directory doesn't exist
            if (!is_dir($translationsPath)) {
                continue;
            }

            // Scan for translation files
            $files = scandir($translationsPath);

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                // Match pattern: messages.{locale}.yaml
                if (!preg_match('/^messages\.([a-z]{2})\.yaml$/', $file, $matches)) {
                    continue;
                }

                $locale = $matches[1];
                $domain = sprintf('plugin_%s', str_replace('-', '_', $plugin->getName()));

                try {
                    $this->translator->addResource(
                        'yaml',
                        "$translationsPath/$file",
                        $locale,
                        $domain
                    );

                    $this->logger->debug("Registered translation for plugin", [
                        'plugin' => $plugin->getName(),
                        'locale' => $locale,
                        'domain' => $domain,
                        'file' => $file,
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error("Failed to register translation", [
                        'plugin' => $plugin->getName(),
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get Twig namespace for a plugin.
     *
     * Converts plugin-name to PluginName (PascalCase).
     *
     * Example: hello-world → Plugin HelloWorld
     *
     * @param string $pluginName Plugin name (kebab-case)
     * @return string Twig namespace (e.g., "PluginHelloWorld")
     */
    private function getPluginTwigNamespace(string $pluginName): string
    {
        return 'Plugin' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
    }
}
