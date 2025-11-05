<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry for plugin console commands.
 *
 * Discovers, instantiates, and registers console commands from enabled plugins
 * with the 'console' capability. Commands are automatically added/removed when
 * plugins are enabled/disabled.
 */
class PluginCommandRegistry
{
    /** @var array<string, Command[]> Registered commands by plugin name */
    private array $registeredCommands = [];

    private ?Application $application = null;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Set the console application instance.
     * Called by console event listener when application is available.
     */
    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }

    /**
     * Register all console commands for a plugin.
     *
     * @param Plugin $plugin Plugin to register commands for
     * @throws RuntimeException If command instantiation fails
     */
    public function registerCommands(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('console')) {
            $this->logger->debug('Plugin does not have console capability, skipping command registration', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        if (!$plugin->isEnabled()) {
            $this->logger->warning('Cannot register commands for disabled plugin', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        try {
            $commands = $this->discoverCommands($plugin);

            if (empty($commands)) {
                $this->logger->debug('No console commands found for plugin', [
                    'plugin' => $plugin->getName(),
                ]);
                return;
            }

            foreach ($commands as $commandClass) {
                $command = $this->instantiateCommand($commandClass);

                if ($command === null) {
                    $this->logger->warning('Failed to instantiate command', [
                        'plugin' => $plugin->getName(),
                        'class' => $commandClass,
                    ]);
                    continue;
                }

                // Register command with console application (if available)
                $this->application?->add($command);

                // Track registered command
                if (!isset($this->registeredCommands[$plugin->getName()])) {
                    $this->registeredCommands[$plugin->getName()] = [];
                }
                $this->registeredCommands[$plugin->getName()][] = $command;

                $this->logger->info('Registered console command', [
                    'plugin' => $plugin->getName(),
                    'class' => $commandClass,
                    'name' => $command->getName(),
                ]);
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to register console commands', [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException(
                sprintf('Failed to register console commands for plugin "%s": %s', $plugin->getName(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Unregister all console commands for a plugin.
     *
     * Note: Symfony Application doesn't support removing commands dynamically,
     * so we just clear our tracking. Commands will not be available after restart.
     *
     * @param Plugin $plugin Plugin to unregister commands for
     */
    public function unregisterCommands(Plugin $plugin): void
    {
        $pluginName = $plugin->getName();

        if (!isset($this->registeredCommands[$pluginName])) {
            $this->logger->debug('No commands registered for plugin', [
                'plugin' => $pluginName,
            ]);
            return;
        }

        foreach ($this->registeredCommands[$pluginName] as $command) {
            try {
                // Symfony doesn't support removing commands at runtime,
                // but we can hide them
                $command->setHidden();

                $this->logger->info('Hidden console command (requires restart to fully unregister)', [
                    'plugin' => $pluginName,
                    'class' => get_class($command),
                    'name' => $command->getName(),
                ]);
            } catch (Exception $e) {
                $this->logger->error('Failed to hide console command', [
                    'plugin' => $pluginName,
                    'class' => get_class($command),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        unset($this->registeredCommands[$pluginName]);
    }

    /**
     * Get all registered commands for a plugin.
     *
     * @param string $pluginName Plugin name
     * @return Command[]
     */
    public function getRegisteredCommands(string $pluginName): array
    {
        return $this->registeredCommands[$pluginName] ?? [];
    }

    /**
     * Get all registered commands across all plugins.
     *
     * @return array<string, Command[]> Commands by plugin name
     */
    public function getAllRegisteredCommands(): array
    {
        return $this->registeredCommands;
    }

    /**
     * Discover console command classes for a plugin.
     *
     * Scans the plugin's Command directory and returns fully qualified
     * class names of classes that extend Symfony Command.
     *
     * @param Plugin $plugin Plugin to discover commands for
     * @return string[] Array of fully qualified command class names
     */
    private function discoverCommands(Plugin $plugin): array
    {
        $commandsPath = $plugin->getPath() . '/src/Command';

        if (!is_dir($commandsPath)) {
            return [];
        }

        $commands = [];
        $files = glob($commandsPath . '/*Command.php');

        if ($files === false) {
            return [];
        }

        $pluginNamespace = $this->getPluginNamespace($plugin->getName());

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = $pluginNamespace . 'Command\\' . $className;

            // Check if class exists
            if (!class_exists($fullClassName)) {
                $this->logger->warning('Command class not found', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                    'file' => $file,
                ]);
                continue;
            }

            // Check if class extends Command
            if (!is_subclass_of($fullClassName, Command::class)) {
                $this->logger->warning('Command class does not extend Symfony Command', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                ]);
                continue;
            }

            // Check if command has a name (via AsCommand attribute or static property)
            if (!$this->hasCommandName($fullClassName)) {
                $this->logger->warning('Command class does not define a name', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                ]);
                continue;
            }

            $commands[] = $fullClassName;
        }

        return $commands;
    }

    /**
     * Check if command class has a name defined.
     *
     * @param string $commandClass Command class name
     * @return bool
     */
    private function hasCommandName(string $commandClass): bool
    {
        try {
            $reflection = new ReflectionClass($commandClass);

            // Check for AsCommand attribute (PHP 8+)
            $attributes = $reflection->getAttributes(AsCommand::class);
            if (!empty($attributes)) {
                return true;
            }

            // Check for static $defaultName property
            if ($reflection->hasProperty('defaultName')) {
                $property = $reflection->getProperty('defaultName');
                if ($property->isStatic()) {
                    return true;
                }
            }

            return false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Instantiate a console command.
     *
     * Attempts to instantiate the command using the service container first,
     * falling back to direct instantiation with logger injection.
     *
     * @param string $commandClass Fully qualified command class name
     * @return Command|null Instantiated command or null on failure
     */
    private function instantiateCommand(string $commandClass): ?Command
    {
        try {
            // Try to get from service container first (if registered)
            if ($this->container->has($commandClass)) {
                $command = $this->container->get($commandClass);
                if (!$command instanceof Command) {
                    $this->logger->error('Service is not a Command instance', [
                        'class' => $commandClass,
                        'actual_type' => get_debug_type($command),
                    ]);
                    return null;
                }
                return $command;
            }

            // Fall back to manual instantiation with logger injection
            $reflection = new ReflectionClass($commandClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                // No constructor, simple instantiation
                return new $commandClass();
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
                        'class' => $commandClass,
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
            $this->logger->error('Failed to instantiate command', [
                'class' => $commandClass,
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
