<?php

namespace App\Core\Service\Plugin;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Abstract base class for plugin event subscribers.
 *
 * Plugin event subscribers should extend this class to get access to common
 * services and utilities. This class implements EventSubscriberInterface,
 * so extending classes must implement getSubscribedEvents().
 *
 * Example usage:
 *
 * ```php
 * namespace Plugins\MyPlugin\EventSubscriber;
 *
 * use App\Core\Service\Plugin\AbstractPluginEventSubscriber;
 * use App\Core\Event\Plugin\PluginEnabledEvent;
 *
 * class MyPluginEventSubscriber extends AbstractPluginEventSubscriber
 * {
 *     public static function getSubscribedEvents(): array
 *     {
 *         return [
 *             PluginEnabledEvent::class => 'onPluginEnabled',
 *         ];
 *     }
 *
 *     public function onPluginEnabled(PluginEnabledEvent $event): void
 *     {
 *         $this->logger->info('Plugin enabled: ' . $event->getPlugin()->getName());
 *     }
 * }
 * ```
 */
abstract readonly class AbstractPluginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * Get the plugin name from the class namespace.
     *
     * This extracts the plugin name from the fully qualified class name.
     * For example: Plugins\HelloWorld\EventSubscriber\MySubscriber -> hello-world
     *
     * @return string Plugin name in kebab-case
     */
    protected function getPluginName(): string
    {
        $className = static::class;

        // Extract plugin name from namespace (e.g., Plugins\HelloWorld\... -> HelloWorld)
        if (preg_match('/^Plugins\\\\([^\\\\]+)/', $className, $matches)) {
            $pluginClassName = $matches[1];

            // Convert from PascalCase to kebab-case
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $pluginClassName));
        }

        return 'unknown';
    }

    /**
     * Log an informational message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->info($message, $context);
    }

    /**
     * Log a warning message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logError(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->error($message, $context);
    }

    /**
     * Log a debug message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->debug($message, $context);
    }
}
