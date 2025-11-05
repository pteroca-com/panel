<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry for plugin event subscribers.
 *
 * Discovers, instantiates, and registers event subscribers from enabled plugins
 * with the 'eda' capability. Subscribers are automatically added/removed when
 * plugins are enabled/disabled.
 */
class PluginEventSubscriberRegistry
{
    /** @var array<string, EventSubscriberInterface[]> Registered subscribers by plugin name */
    private array $registeredSubscribers = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Register all event subscribers for a plugin.
     *
     * @param Plugin $plugin Plugin to register subscribers for
     * @throws RuntimeException If subscriber instantiation fails
     */
    public function registerSubscribers(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('eda')) {
            $this->logger->debug('Plugin does not have EDA capability, skipping subscriber registration', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        if (!$plugin->isEnabled()) {
            $this->logger->warning('Cannot register subscribers for disabled plugin', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        try {
            $subscribers = $this->discoverSubscribers($plugin);

            if (empty($subscribers)) {
                $this->logger->debug('No event subscribers found for plugin', [
                    'plugin' => $plugin->getName(),
                ]);
                return;
            }

            foreach ($subscribers as $subscriberClass) {
                $subscriber = $this->instantiateSubscriber($subscriberClass);

                if ($subscriber === null) {
                    $this->logger->warning('Failed to instantiate subscriber', [
                        'plugin' => $plugin->getName(),
                        'class' => $subscriberClass,
                    ]);
                    continue;
                }

                // Register subscriber with event dispatcher
                $this->eventDispatcher->addSubscriber($subscriber);

                // Track registered subscriber
                if (!isset($this->registeredSubscribers[$plugin->getName()])) {
                    $this->registeredSubscribers[$plugin->getName()] = [];
                }
                $this->registeredSubscribers[$plugin->getName()][] = $subscriber;

                $this->logger->info('Registered event subscriber', [
                    'plugin' => $plugin->getName(),
                    'class' => $subscriberClass,
                    'events' => array_keys($subscriber::getSubscribedEvents()),
                ]);
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to register event subscribers', [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException(
                sprintf('Failed to register event subscribers for plugin "%s": %s', $plugin->getName(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Unregister all event subscribers for a plugin.
     *
     * @param Plugin $plugin Plugin to unregister subscribers for
     */
    public function unregisterSubscribers(Plugin $plugin): void
    {
        $pluginName = $plugin->getName();

        if (!isset($this->registeredSubscribers[$pluginName])) {
            $this->logger->debug('No subscribers registered for plugin', [
                'plugin' => $pluginName,
            ]);
            return;
        }

        foreach ($this->registeredSubscribers[$pluginName] as $subscriber) {
            try {
                $this->eventDispatcher->removeSubscriber($subscriber);

                $this->logger->info('Unregistered event subscriber', [
                    'plugin' => $pluginName,
                    'class' => get_class($subscriber),
                ]);
            } catch (Exception $e) {
                $this->logger->error('Failed to unregister event subscriber', [
                    'plugin' => $pluginName,
                    'class' => get_class($subscriber),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        unset($this->registeredSubscribers[$pluginName]);
    }

    /**
     * Get all registered subscribers for a plugin.
     *
     * @param string $pluginName Plugin name
     * @return EventSubscriberInterface[]
     */
    public function getRegisteredSubscribers(string $pluginName): array
    {
        return $this->registeredSubscribers[$pluginName] ?? [];
    }

    /**
     * Get all registered subscribers across all plugins.
     *
     * @return array<string, EventSubscriberInterface[]> Subscribers by plugin name
     */
    public function getAllRegisteredSubscribers(): array
    {
        return $this->registeredSubscribers;
    }

    /**
     * Discover event subscriber classes for a plugin.
     *
     * Scans the plugin's EventSubscriber directory and returns fully qualified
     * class names of classes that implement EventSubscriberInterface.
     *
     * @param Plugin $plugin Plugin to discover subscribers for
     * @return string[] Array of fully qualified subscriber class names
     */
    private function discoverSubscribers(Plugin $plugin): array
    {
        $subscribersPath = $plugin->getPath() . '/src/EventSubscriber';

        if (!is_dir($subscribersPath)) {
            return [];
        }

        $subscribers = [];
        $files = glob($subscribersPath . '/*Subscriber.php');

        if ($files === false) {
            return [];
        }

        $pluginNamespace = $this->getPluginNamespace($plugin->getName());

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = $pluginNamespace . 'EventSubscriber\\' . $className;

            // Check if class exists
            if (!class_exists($fullClassName)) {
                $this->logger->warning('Subscriber class not found', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                    'file' => $file,
                ]);
                continue;
            }

            // Check if class implements EventSubscriberInterface
            if (!is_subclass_of($fullClassName, EventSubscriberInterface::class)) {
                $this->logger->warning('Subscriber class does not implement EventSubscriberInterface', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                ]);
                continue;
            }

            $subscribers[] = $fullClassName;
        }

        return $subscribers;
    }

    /**
     * Instantiate an event subscriber.
     *
     * Attempts to instantiate the subscriber using the service container first,
     * falling back to direct instantiation with logger injection.
     *
     * @param string $subscriberClass Fully qualified subscriber class name
     * @return EventSubscriberInterface|null Instantiated subscriber or null on failure
     */
    private function instantiateSubscriber(string $subscriberClass): ?EventSubscriberInterface
    {
        try {
            // Try to get from service container first (if registered)
            if ($this->container->has($subscriberClass)) {
                $subscriber = $this->container->get($subscriberClass);
                if (!$subscriber instanceof EventSubscriberInterface) {
                    $this->logger->error('Service is not an EventSubscriberInterface instance', [
                        'class' => $subscriberClass,
                        'actual_type' => get_debug_type($subscriber),
                    ]);
                    return null;
                }
                return $subscriber;
            }

            // Fall back to manual instantiation with logger injection
            $reflection = new ReflectionClass($subscriberClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                // No constructor, simple instantiation
                return new $subscriberClass();
            }

            // Try to inject common dependencies
            $parameters = $constructor->getParameters();
            $args = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    // Inject logger if requested
                    if ($typeName === LoggerInterface::class || $typeName === 'Psr\Log\LoggerInterface') {
                        $args[] = $this->logger;
                        continue;
                    }

                    // Try to get from container
                    if ($this->container->has($typeName)) {
                        $args[] = $this->container->get($typeName);
                        continue;
                    }
                }

                // Parameter not resolvable
                if (!$parameter->isOptional()) {
                    $this->logger->error('Cannot resolve required constructor parameter', [
                        'class' => $subscriberClass,
                        'parameter' => $parameter->getName(),
                        'type' => $type ? $type->getName() : 'none',
                    ]);
                    return null;
                }

                // Use default value for optional parameter
                $args[] = $parameter->getDefaultValue();
            }

            return $reflection->newInstanceArgs($args);

        } catch (Exception $e) {
            $this->logger->error('Failed to instantiate subscriber', [
                'class' => $subscriberClass,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get plugin namespace from plugin name.
     *
     * Converts plugin name (e.g., "hello-world") to namespace (e.g., "Plugins\HelloWorld\")
     *
     * @param string $pluginName Plugin name in kebab-case
     * @return string Plugin namespace
     */
    private function getPluginNamespace(string $pluginName): string
    {
        // Convert hello-world to HelloWorld
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\$className\\";
    }
}
