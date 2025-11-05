<?php

namespace App\Core\Service\Plugin;

use App\Core\Contract\Plugin\PluginCronTaskInterface;
use App\Core\Entity\Plugin;
use App\Core\Repository\PluginRepository;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry for plugin cron tasks.
 *
 * Discovers, instantiates, and manages cron tasks from enabled plugins
 * with the 'cron' capability. Tasks are stored in-memory and can be
 * executed by the CronScheduler service.
 */
class PluginCronRegistry
{
    /**
     * @var array<string, PluginCronTaskInterface> Registered tasks indexed by task name
     * Format: ['plugin-name:task-name' => PluginCronTaskInterface, ...]
     */
    private array $registeredTasks = [];

    /**
     * @var array<string, string[]> Task names grouped by plugin name
     * Format: ['plugin-name' => ['plugin-name:task-name', ...], ...]
     */
    private array $tasksByPlugin = [];

    /** @var bool Whether tasks have been loaded from enabled plugins */
    private bool $tasksLoaded = false;

    public function __construct(
        private readonly PluginRepository $pluginRepository,
        private readonly PluginAutoloader $autoloader,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Register all cron tasks for a plugin.
     *
     * @param Plugin $plugin Plugin to register tasks for
     * @throws RuntimeException If task instantiation fails
     */
    public function registerTasks(Plugin $plugin): void
    {
        if (!$plugin->hasCapability('cron')) {
            $this->logger->debug('Plugin does not have cron capability, skipping task registration', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        if (!$plugin->isEnabled()) {
            $this->logger->warning('Cannot register tasks for disabled plugin', [
                'plugin' => $plugin->getName(),
            ]);
            return;
        }

        try {
            $tasks = $this->discoverTasks($plugin);

            if (empty($tasks)) {
                $this->logger->debug('No cron tasks found for plugin', [
                    'plugin' => $plugin->getName(),
                ]);
                return;
            }

            foreach ($tasks as $taskClass) {
                $task = $this->instantiateTask($taskClass);

                if ($task === null) {
                    $this->logger->warning('Failed to instantiate cron task', [
                        'plugin' => $plugin->getName(),
                        'class' => $taskClass,
                    ]);
                    continue;
                }

                $taskName = $task->getName();

                // Check for duplicate task names
                if (isset($this->registeredTasks[$taskName])) {
                    $this->logger->warning('Task with this name is already registered, skipping', [
                        'plugin' => $plugin->getName(),
                        'task_name' => $taskName,
                        'class' => $taskClass,
                    ]);
                    continue;
                }

                // Register task
                $this->registeredTasks[$taskName] = $task;

                // Track by plugin
                if (!isset($this->tasksByPlugin[$plugin->getName()])) {
                    $this->tasksByPlugin[$plugin->getName()] = [];
                }
                $this->tasksByPlugin[$plugin->getName()][] = $taskName;

                $this->logger->info('Registered cron task', [
                    'plugin' => $plugin->getName(),
                    'class' => $taskClass,
                    'task_name' => $taskName,
                    'schedule' => $task->getSchedule(),
                    'description' => $task->getDescription(),
                ]);
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to register cron tasks', [
                'plugin' => $plugin->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException(
                sprintf('Failed to register cron tasks for plugin "%s": %s', $plugin->getName(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Unregister all cron tasks for a plugin.
     *
     * @param Plugin $plugin Plugin to unregister tasks for
     */
    public function unregisterTasks(Plugin $plugin): void
    {
        $pluginName = $plugin->getName();

        if (!isset($this->tasksByPlugin[$pluginName])) {
            $this->logger->debug('No tasks registered for plugin', [
                'plugin' => $pluginName,
            ]);
            return;
        }

        foreach ($this->tasksByPlugin[$pluginName] as $taskName) {
            unset($this->registeredTasks[$taskName]);

            $this->logger->info('Unregistered cron task', [
                'plugin' => $pluginName,
                'task_name' => $taskName,
            ]);
        }

        unset($this->tasksByPlugin[$pluginName]);
    }

    /**
     * Get a specific task by name.
     *
     * @param string $taskName Task name (format: "plugin-name:task-name")
     * @return PluginCronTaskInterface|null Task instance or null if not found
     */
    public function getTask(string $taskName): ?PluginCronTaskInterface
    {
        $this->ensureTasksLoaded();
        return $this->registeredTasks[$taskName] ?? null;
    }

    /**
     * Get all registered tasks for a specific plugin.
     *
     * @param string $pluginName Plugin name
     * @return PluginCronTaskInterface[]
     */
    public function getTasksByPlugin(string $pluginName): array
    {
        $this->ensureTasksLoaded();

        if (!isset($this->tasksByPlugin[$pluginName])) {
            return [];
        }

        $tasks = [];
        foreach ($this->tasksByPlugin[$pluginName] as $taskName) {
            if (isset($this->registeredTasks[$taskName])) {
                $tasks[] = $this->registeredTasks[$taskName];
            }
        }

        return $tasks;
    }

    /**
     * Get all registered tasks across all plugins.
     *
     * @return PluginCronTaskInterface[]
     */
    public function getAllTasks(): array
    {
        $this->ensureTasksLoaded();
        return array_values($this->registeredTasks);
    }

    /**
     * Get all registered tasks indexed by task name.
     *
     * @return array<string, PluginCronTaskInterface>
     */
    public function getAllTasksIndexed(): array
    {
        $this->ensureTasksLoaded();
        return $this->registeredTasks;
    }

    /**
     * Get tasks that match a specific cron schedule.
     *
     * @param string $schedule Cron expression
     * @return PluginCronTaskInterface[]
     */
    public function getTasksBySchedule(string $schedule): array
    {
        $this->ensureTasksLoaded();
        return array_filter(
            $this->registeredTasks,
            fn(PluginCronTaskInterface $task) => $task->getSchedule() === $schedule
        );
    }

    /**
     * Get enabled tasks only.
     *
     * @return PluginCronTaskInterface[]
     */
    public function getEnabledTasks(): array
    {
        $this->ensureTasksLoaded();
        return array_filter(
            $this->registeredTasks,
            fn(PluginCronTaskInterface $task) => $task->isEnabled()
        );
    }

    /**
     * Check if a plugin has any registered tasks.
     *
     * @param string $pluginName Plugin name
     * @return bool
     */
    public function hasTasksForPlugin(string $pluginName): bool
    {
        return isset($this->tasksByPlugin[$pluginName]) && !empty($this->tasksByPlugin[$pluginName]);
    }

    /**
     * Get count of registered tasks.
     *
     * @return int
     */
    public function getTaskCount(): int
    {
        return count($this->registeredTasks);
    }

    /**
     * Ensure all tasks from enabled plugins are loaded.
     *
     * This method loads tasks from all enabled plugins with the 'cron' capability
     * on first access. Subsequent calls are no-ops.
     */
    private function ensureTasksLoaded(): void
    {
        if ($this->tasksLoaded) {
            return;
        }

        try {
            $enabledPlugins = $this->pluginRepository->findEnabled();

            foreach ($enabledPlugins as $plugin) {
                if ($plugin->hasCapability('cron')) {
                    // Register plugin autoloading first
                    $this->autoloader->registerPlugin($plugin);

                    // Register tasks without throwing exceptions
                    try {
                        $this->registerTasks($plugin);
                    } catch (Exception $e) {
                        // Log but don't fail - allow other plugins to load
                        $this->logger->error('Failed to auto-load cron tasks for plugin', [
                            'plugin' => $plugin->getName(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Register core system tasks
            $this->registerCoreSystemTasks();

            $this->tasksLoaded = true;
        } catch (Exception $e) {
            $this->logger->error('Failed to load cron tasks from enabled plugins', [
                'error' => $e->getMessage(),
            ]);
            // Mark as loaded anyway to prevent repeated failures
            $this->tasksLoaded = true;
        }
    }

    /**
     * Register core system cron tasks.
     *
     * Discovers and registers cron tasks from the core system
     * (src/Core/Service/Plugin/CronTask directory).
     */
    private function registerCoreSystemTasks(): void
    {
        $tasksPath = __DIR__ . '/CronTask';

        if (!is_dir($tasksPath)) {
            $this->logger->debug('Core system cron tasks directory not found');
            return;
        }

        try {
            $files = glob($tasksPath . '/*Task.php');

            if (empty($files)) {
                $this->logger->debug('No core system cron tasks found');
                return;
            }

            foreach ($files as $file) {
                $className = basename($file, '.php');
                $fullClassName = 'App\\Core\\Service\\Plugin\\CronTask\\' . $className;

                // Check if class exists
                if (!class_exists($fullClassName)) {
                    $this->logger->warning('Core system task class not found', [
                        'class' => $fullClassName,
                        'file' => $file,
                    ]);
                    continue;
                }

                // Check if class implements PluginCronTaskInterface
                if (!is_subclass_of($fullClassName, PluginCronTaskInterface::class)) {
                    $this->logger->warning('Core system task does not implement PluginCronTaskInterface', [
                        'class' => $fullClassName,
                    ]);
                    continue;
                }

                // Instantiate task
                $task = $this->instantiateTask($fullClassName);

                if ($task === null) {
                    $this->logger->warning('Failed to instantiate core system task', [
                        'class' => $fullClassName,
                    ]);
                    continue;
                }

                $taskName = 'system:' . $task->getName();

                // Check for duplicate task names
                if (isset($this->registeredTasks[$taskName])) {
                    $this->logger->warning('Core system task with this name is already registered, skipping', [
                        'task_name' => $taskName,
                        'class' => $fullClassName,
                    ]);
                    continue;
                }

                // Register task
                $this->registeredTasks[$taskName] = $task;

                // Track by "system" as plugin name
                if (!isset($this->tasksByPlugin['system'])) {
                    $this->tasksByPlugin['system'] = [];
                }
                $this->tasksByPlugin['system'][] = $taskName;

                $this->logger->info('Registered core system cron task', [
                    'class' => $fullClassName,
                    'task_name' => $taskName,
                    'schedule' => $task->getSchedule(),
                    'description' => $task->getDescription(),
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to register core system cron tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - allow plugin system to continue without core tasks
        }
    }

    /**
     * Discover cron task classes for a plugin.
     *
     * Scans the plugin's CronTask directory and returns fully qualified
     * class names of classes that implement PluginCronTaskInterface.
     *
     * @param Plugin $plugin Plugin to discover tasks for
     * @return string[] Array of fully qualified task class names
     */
    private function discoverTasks(Plugin $plugin): array
    {
        $tasksPath = $plugin->getPath() . '/src/CronTask';

        if (!is_dir($tasksPath)) {
            return [];
        }

        $tasks = [];
        $files = glob($tasksPath . '/*Task.php');

        if ($files === false) {
            return [];
        }

        $pluginNamespace = $this->getPluginNamespace($plugin->getName());

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = $pluginNamespace . 'CronTask\\' . $className;

            // Check if class exists
            if (!class_exists($fullClassName)) {
                $this->logger->warning('Task class not found', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                    'file' => $file,
                ]);
                continue;
            }

            // Check if class implements PluginCronTaskInterface
            if (!is_subclass_of($fullClassName, PluginCronTaskInterface::class)) {
                $this->logger->warning('Task class does not implement PluginCronTaskInterface', [
                    'plugin' => $plugin->getName(),
                    'class' => $fullClassName,
                ]);
                continue;
            }

            $tasks[] = $fullClassName;
        }

        return $tasks;
    }

    /**
     * Instantiate a cron task.
     *
     * Attempts to instantiate the task using the service container first,
     * falling back to direct instantiation with common dependency injection.
     *
     * @param string $taskClass Fully qualified task class name
     * @return PluginCronTaskInterface|null Instantiated task or null on failure
     */
    private function instantiateTask(string $taskClass): ?PluginCronTaskInterface
    {
        try {
            // Try to get from service container first (if registered)
            if ($this->container->has($taskClass)) {
                $task = $this->container->get($taskClass);
                if (!$task instanceof PluginCronTaskInterface) {
                    $this->logger->error('Service is not a PluginCronTaskInterface instance', [
                        'class' => $taskClass,
                        'actual_type' => get_debug_type($task),
                    ]);
                    return null;
                }
                return $task;
            }

            // Fall back to manual instantiation with dependency injection
            $reflection = new ReflectionClass($taskClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                // No constructor, simple instantiation
                return new $taskClass();
            }

            // Try to inject common dependencies
            $parameters = $constructor->getParameters();
            $args = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    // Inject logger if requested
                    if ($typeName === LoggerInterface::class || $typeName === 'Psr\\Log\\LoggerInterface') {
                        $args[] = $this->logger;
                        continue;
                    }

                    // Inject entity manager if requested
                    if ($typeName === 'Doctrine\\ORM\\EntityManagerInterface') {
                        if ($this->container->has('doctrine.orm.entity_manager')) {
                            $args[] = $this->container->get('doctrine.orm.entity_manager');
                            continue;
                        }
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
                        'class' => $taskClass,
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
            $this->logger->error('Failed to instantiate cron task', [
                'class' => $taskClass,
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
