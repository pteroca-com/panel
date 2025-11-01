<?php

declare(strict_types=1);

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers Twig namespaces for enabled plugins.
 *
 * For each enabled plugin with 'ui' capability:
 * - Registers namespace: @Plugin{PluginName}/...
 * - Path: /plugins/{plugin-name}/templates/
 *
 * Example:
 * - Plugin "hello-world" with capability "ui"
 * - Namespace: @PluginHelloWorld/
 * - Path: /plugins/hello-world/templates/
 *
 * Templates can then be rendered as:
 * return $this->render('@PluginHelloWorld/index.html.twig');
 */
class PluginTwigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if Twig is available
        if (!$container->hasDefinition('twig.loader.native_filesystem')) {
            return;
        }

        $twigFilesystemLoader = $container->getDefinition('twig.loader.native_filesystem');
        $projectDir = $container->getParameter('kernel.project_dir');

        // Get all enabled plugins from database
        $enabledPlugins = $this->getEnabledPlugins($container);

        foreach ($enabledPlugins as $pluginData) {
            $this->registerPluginTwigNamespace(
                $twigFilesystemLoader,
                $pluginData,
                $projectDir
            );
        }
    }

    /**
     * Query database for enabled plugins during container compilation.
     */
    private function getEnabledPlugins(ContainerBuilder $container): array
    {
        try {
            // Get database URL from environment
            $databaseUrl = getenv('DATABASE_URL');
            if (!$databaseUrl) {
                error_log('DATABASE_URL not set, skipping plugin Twig namespace registration');
                return [];
            }

            // Parse database URL to extract connection details
            $parsedUrl = parse_url($databaseUrl);
            if (!$parsedUrl) {
                error_log('Invalid DATABASE_URL format');
                return [];
            }

            // Extract connection parameters
            $scheme = $parsedUrl['scheme'] ?? '';
            $host = $parsedUrl['host'] ?? 'localhost';
            $dbname = ltrim($parsedUrl['path'] ?? '', '/');
            $user = $parsedUrl['user'] ?? '';
            $password = $parsedUrl['pass'] ?? '';

            // Determine driver and default port
            if (in_array($scheme, ['mysql', 'mysqli'])) {
                $driver = 'mysql';
                $port = $parsedUrl['port'] ?? 3306;
            } elseif (in_array($scheme, ['postgresql', 'postgres', 'pgsql'])) {
                $driver = 'pgsql';
                $port = $parsedUrl['port'] ?? 5432;
            } else {
                error_log("Unsupported database driver: {$scheme}");
                return [];
            }

            // Connect to database
            $dsn = sprintf('%s:host=%s;port=%d;dbname=%s', $driver, $host, $port, $dbname);
            $pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            // Query enabled plugins
            $stmt = $pdo->query(
                "SELECT name, manifest
                FROM plugin
                WHERE state = 'enabled'
                ORDER BY name"
            );

            $plugins = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $manifest = json_decode($row['manifest'], true);
                if ($manifest) {
                    $plugins[] = [
                        'name' => $row['name'],
                        'manifest' => $manifest,
                    ];
                }
            }

            return $plugins;
        } catch (\Exception $e) {
            error_log("Could not load plugins during Twig compilation: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Register Twig namespace for a plugin.
     *
     * @param mixed $twigFilesystemLoader The Twig filesystem loader definition
     * @param array $pluginData Plugin data with 'name' and 'manifest'
     * @param string $projectDir Project root directory
     */
    private function registerPluginTwigNamespace(
        $twigFilesystemLoader,
        array $pluginData,
        string $projectDir
    ): void {
        $pluginName = $pluginData['name'];
        $manifest = $pluginData['manifest'];

        // Check if plugin has 'ui' capability
        if (!in_array('ui', $manifest['capabilities'], true)) {
            return;
        }

        $templatePath = $projectDir . '/plugins/' . $pluginName . '/templates';

        // Check if templates directory exists
        if (!is_dir($templatePath)) {
            return;
        }

        // Convert plugin name to namespace
        // "hello-world" -> "PluginHelloWorld"
        $namespace = $this->getPluginTwigNamespace($pluginName);

        // Register namespace with Twig
        $twigFilesystemLoader->addMethodCall('addPath', [$templatePath, $namespace]);
    }

    /**
     * Convert plugin name to Twig namespace.
     *
     * Examples:
     * - "hello-world" -> "PluginHelloWorld"
     * - "payment-gateway" -> "PluginPaymentGateway"
     * - "my_awesome_plugin" -> "PluginMyAwesomePlugin"
     *
     * @param string $pluginName Plugin name (e.g., "hello-world")
     * @return string Twig namespace (e.g., "PluginHelloWorld")
     */
    private function getPluginTwigNamespace(string $pluginName): string
    {
        // Convert to PascalCase
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return 'Plugin' . $className;
    }
}
