<?php

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers plugin console commands during container compilation.
 */
class PluginConsoleCommandCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Get project directory
        $projectDir = $container->getParameter('kernel.project_dir');

        // Get enabled plugins from database
        $enabledPlugins = $this->getEnabledPlugins($container);

        if (empty($enabledPlugins)) {
            return; // No plugins to load
        }

        foreach ($enabledPlugins as $pluginData) {
            // Check if plugin has 'console' capability
            if (!in_array('console', $pluginData['manifest']['capabilities'] ?? [], true)) {
                continue;
            }

            $this->registerConsoleCommands($container, $pluginData, $projectDir);
        }
    }

    private function registerConsoleCommands(ContainerBuilder $container, array $pluginData, string $projectDir): void
    {
        $pluginName = $pluginData['name'];
        $pluginPath = $projectDir . '/plugins/' . $pluginName;
        $commandPath = $pluginPath . '/src/Command';

        // Check if Command directory exists
        if (!is_dir($commandPath)) {
            return;
        }

        // Register autoloader for this plugin
        $this->registerPluginAutoloader($pluginName, $pluginPath);

        // Get plugin namespace
        $pluginNamespace = $this->getPluginNamespace($pluginName);

        try {
            // Find all PHP files in Command directory
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($commandPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Extract class name from file
                $relativePath = str_replace($commandPath . '/', '', $file->getPathname());
                $className = $pluginNamespace . '\\Command\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

                // Verify class exists and extends Command
                if (!class_exists($className)) {
                    error_log("Command class not found: {$className}");
                    continue;
                }

                if (!is_subclass_of($className, Command::class)) {
                    error_log("Class {$className} does not extend Symfony\\Component\\Console\\Command\\Command");
                    continue;
                }

                // Register as service with console.command tag
                $definition = $container->register($className, $className);
                $definition->setPublic(false);
                $definition->setAutowired(true);
                $definition->setAutoconfigured(true);
                $definition->addTag('console.command');
            }

        } catch (\Exception $e) {
            error_log("Failed to register console commands for plugin {$pluginName}: {$e->getMessage()}");
        }
    }

    private function getEnabledPlugins(ContainerBuilder $container): array
    {
        try {
            // Get database URL from environment variable
            $databaseUrl = getenv('DATABASE_URL');

            // Check if database URL is set
            if (empty($databaseUrl)) {
                // Try to get from container parameter (might be set in parameters.yaml)
                if ($container->hasParameter('database_url')) {
                    $databaseUrl = $container->getParameter('database_url');
                } else {
                    return []; // No database configured
                }
            }

            // Create temporary PDO connection (supports MySQL and PostgreSQL)
            $pdo = $this->createDatabaseConnection($databaseUrl);

            if (!$pdo) {
                return [];
            }

            // Query for enabled plugins
            $stmt = $pdo->query("
                SELECT name, manifest
                FROM plugin
                WHERE state = 'enabled'
                ORDER BY name ASC
            ");

            if (!$stmt) {
                return [];
            }

            $plugins = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $plugins[] = [
                    'name' => $row['name'],
                    'manifest' => json_decode($row['manifest'], true),
                ];
            }

            return $plugins;

        } catch (\Exception $e) {
            // Silently fail - plugin table might not exist yet during initial setup
            error_log("Could not load plugins during compilation: {$e->getMessage()}");
            return [];
        }
    }

    private function createDatabaseConnection(string $databaseUrl): ?\PDO
    {
        try {
            // Parse DATABASE_URL (format: mysql://user:pass@host:port/dbname or postgresql://...)
            $parsed = parse_url($databaseUrl);

            if (!$parsed) {
                return null;
            }

            $scheme = $parsed['scheme'] ?? '';
            $host = $parsed['host'] ?? 'localhost';
            $dbname = ltrim($parsed['path'] ?? '', '/');
            $user = $parsed['user'] ?? '';
            $password = $parsed['pass'] ?? '';

            // Determine driver and default port
            if (in_array($scheme, ['mysql', 'mysqli'])) {
                $driver = 'mysql';
                $port = $parsed['port'] ?? 3306;
            } elseif (in_array($scheme, ['postgresql', 'postgres', 'pgsql'])) {
                $driver = 'pgsql';
                $port = $parsed['port'] ?? 5432;
            } else {
                error_log("Unsupported database driver: {$scheme}");
                return null;
            }

            $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname}";

            return new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

        } catch (\Exception $e) {
            error_log("Failed to create database connection: {$e->getMessage()}");
            return null;
        }
    }

    private function getPluginNamespace(string $pluginName): string
    {
        // Convert "hello-world" to "HelloWorld"
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\{$className}";
    }

    private function registerPluginAutoloader(string $pluginName, string $pluginPath): void
    {
        $srcPath = $pluginPath . '/src';

        if (!is_dir($srcPath)) {
            return;
        }

        $namespace = $this->getPluginNamespace($pluginName) . '\\';

        spl_autoload_register(function ($class) use ($namespace, $srcPath) {
            // Check if class belongs to this namespace
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            // Get relative class name
            $relativeClass = substr($class, strlen($namespace));

            // Replace namespace separators with directory separators
            $file = $srcPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            // Require if exists
            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
