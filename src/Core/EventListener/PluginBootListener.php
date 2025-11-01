<?php

declare(strict_types=1);

namespace App\Core\EventListener;

use App\Core\Repository\PluginRepository;
use App\Core\Service\Plugin\PluginAutoloader;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener that registers PSR-4 autoloaders for all ENABLED plugins on kernel boot.
 *
 * This ensures that plugin classes are autoloadable when routes are compiled
 * or when controllers are invoked.
 */
class PluginBootListener implements EventSubscriberInterface
{
    private static bool $booted = false;

    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly PluginAutoloader $pluginAutoloader,
        private readonly LoggerInterface $logger,
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

            self::$booted = true;

        } catch (\Exception $e) {
            // Silently fail during boot (e.g., database not available yet)
            $this->logger->warning("Failed to boot plugins: {$e->getMessage()}");
        }
    }
}
