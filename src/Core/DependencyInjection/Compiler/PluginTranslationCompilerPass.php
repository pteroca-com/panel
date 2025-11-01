<?php

declare(strict_types=1);

namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers translation files for enabled plugins.
 *
 * For each enabled plugin with 'ui' capability:
 * - Scans: /plugins/{plugin-name}/translations/
 * - Domain: plugin_{plugin_name}
 * - Supported formats: YAML (.yaml, .yml), XLIFF (.xlf, .xliff), PHP (.php)
 *
 * Example:
 * - Plugin "hello-world"
 * - Path: /plugins/hello-world/translations/messages+intl-icu.pl.yaml
 * - Domain: plugin_hello_world
 *
 * Usage in templates:
 * {{ 'greeting'|trans({}, 'plugin_hello_world') }}
 */
class PluginTranslationCompilerPass implements CompilerPassInterface
{
    private const SUPPORTED_FORMATS = [
        'yaml' => 'yaml',
        'yml' => 'yaml',
        'xlf' => 'xliff',
        'xliff' => 'xliff',
        'php' => 'php',
    ];

    public function process(ContainerBuilder $container): void
    {
        // Check if translator is available
        if (!$container->hasDefinition('translator.default')) {
            return;
        }

        $translator = $container->findDefinition('translator.default');
        $projectDir = $container->getParameter('kernel.project_dir');

        // Get all enabled plugins from database
        $enabledPlugins = $this->getEnabledPlugins($container);

        foreach ($enabledPlugins as $pluginData) {
            $this->registerPluginTranslations(
                $translator,
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
                error_log('DATABASE_URL not set, skipping plugin translation registration');
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
            error_log("Could not load plugins during translation compilation: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Register translation files for a plugin.
     *
     * @param mixed $translator The translator service definition
     * @param array $pluginData Plugin data with 'name' and 'manifest'
     * @param string $projectDir Project root directory
     */
    private function registerPluginTranslations(
        $translator,
        array $pluginData,
        string $projectDir
    ): void {
        $pluginName = $pluginData['name'];
        $manifest = $pluginData['manifest'];

        // Check if plugin has 'ui' capability
        if (!in_array('ui', $manifest['capabilities'], true)) {
            return;
        }

        $translationsPath = $projectDir . '/plugins/' . $pluginName . '/translations';

        // Check if translations directory exists
        if (!is_dir($translationsPath)) {
            return;
        }

        // Get translation domain for this plugin
        $domain = $this->getPluginTranslationDomain($pluginName);

        // Scan translation files
        $translationFiles = $this->scanTranslationFiles($translationsPath);

        foreach ($translationFiles as $file) {
            $this->addTranslationResource($translator, $file, $domain);
        }
    }

    /**
     * Scan translation directory for supported files.
     *
     * @param string $translationsPath Path to translations directory
     * @return array Array of file info with 'path', 'format', 'locale', 'domain'
     */
    private function scanTranslationFiles(string $translationsPath): array
    {
        $files = [];

        if (!is_dir($translationsPath)) {
            return $files;
        }

        $iterator = new \DirectoryIterator($translationsPath);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            $extension = $fileInfo->getExtension();

            // Check if format is supported
            if (!isset(self::SUPPORTED_FORMATS[$extension])) {
                continue;
            }

            // Parse filename: messages+intl-icu.pl.yaml -> domain=messages, locale=pl
            $parsed = $this->parseTranslationFilename($filename);

            if ($parsed) {
                $files[] = [
                    'path' => $fileInfo->getPathname(),
                    'format' => self::SUPPORTED_FORMATS[$extension],
                    'locale' => $parsed['locale'],
                    'domain' => $parsed['domain'],
                ];
            }
        }

        return $files;
    }

    /**
     * Parse translation filename to extract domain and locale.
     *
     * Supported formats:
     * - messages.pl.yaml -> domain=messages, locale=pl
     * - messages+intl-icu.pl.yaml -> domain=messages+intl-icu, locale=pl
     * - validators.en.xlf -> domain=validators, locale=en
     *
     * @param string $filename Translation filename
     * @return array|null Array with 'domain' and 'locale' or null if invalid
     */
    private function parseTranslationFilename(string $filename): ?array
    {
        // Remove extension
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        // Split by last dot to get locale
        $parts = explode('.', $nameWithoutExt);

        if (count($parts) < 2) {
            return null;
        }

        $locale = array_pop($parts);
        $domain = implode('.', $parts);

        return [
            'domain' => $domain,
            'locale' => $locale,
        ];
    }

    /**
     * Add translation resource to translator.
     *
     * @param mixed $translator Translator service definition
     * @param array $file File info with 'path', 'format', 'locale', 'domain'
     * @param string $pluginDomain Plugin-specific domain prefix
     */
    private function addTranslationResource($translator, array $file, string $pluginDomain): void
    {
        // Use plugin-specific domain
        // This ensures plugin translations don't conflict with core translations
        $fullDomain = $pluginDomain;

        // If file has a specific domain (not 'messages'), append it
        if ($file['domain'] !== 'messages' && !str_starts_with($file['domain'], 'messages+')) {
            $fullDomain .= '.' . $file['domain'];
        }

        $translator->addMethodCall('addResource', [
            $file['format'],
            $file['path'],
            $file['locale'],
            $fullDomain,
        ]);
    }

    /**
     * Convert plugin name to translation domain.
     *
     * Examples:
     * - "hello-world" -> "plugin_hello_world"
     * - "payment-gateway" -> "plugin_payment_gateway"
     * - "my_awesome_plugin" -> "plugin_my_awesome_plugin"
     *
     * @param string $pluginName Plugin name (e.g., "hello-world")
     * @return string Translation domain (e.g., "plugin_hello_world")
     */
    private function getPluginTranslationDomain(string $pluginName): string
    {
        // Convert to snake_case
        $domainName = str_replace('-', '_', strtolower($pluginName));
        return 'plugin_' . $domainName;
    }
}
