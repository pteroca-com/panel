<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use ArrayIterator;
use Exception;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * Security validator for plugin code.
 *
 * Performs static analysis to detect:
 * - Dangerous PHP functions (eval, exec, shell_exec, etc.)
 * - Path traversal attempts (../)
 * - Potential SQL injection patterns
 * - XSS vulnerabilities
 * - File permission issues
 *
 * Returns array of security issues with severity levels:
 * - critical: Must be fixed before plugin can be enabled
 * - high: Should be fixed, creates security risk
 * - medium: Potential issue, review recommended
 * - low: Best practice suggestion
 */
class PluginSecurityValidator
{
    private const SEVERITY_CRITICAL = 'critical';
    private const SEVERITY_HIGH = 'high';
    private const SEVERITY_MEDIUM = 'medium';
    private const SEVERITY_LOW = 'low';

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'plugin_security.dangerous_functions')]
        private readonly array $dangerousFunctions,
        #[Autowire(param: 'plugin_security.checks')]
        private readonly array $securityChecks,
    ) {}

    /**
     * Validate plugin security.
     *
     * @param Plugin $plugin Plugin to validate
     * @return array Array of security issues
     * @throws Exception
     */
    public function validate(Plugin $plugin): array
    {
        $issues = [];

        $this->logger->info("Running security validation for plugin", [
            'plugin' => $plugin->getName(),
        ]);

        // Check 1: Dangerous functions
        if ($this->securityChecks['dangerous_functions'] ?? true) {
            $issues = array_merge($issues, $this->scanForDangerousFunctions($plugin->getPath()));
        }

        // Check 2: Path traversal
        if ($this->securityChecks['path_traversal'] ?? true) {
            $issues = array_merge($issues, $this->scanForPathTraversal($plugin->getPath()));
        }

        // Check 3: SQL injection patterns
        if ($this->securityChecks['sql_injection'] ?? true) {
            $issues = array_merge($issues, $this->analyzeDatabaseQueries($plugin->getPath()));
        }

        // Check 4: XSS patterns
        if ($this->securityChecks['xss_patterns'] ?? true) {
            $issues = array_merge($issues, $this->scanForXSSPatterns($plugin->getPath()));
        }

        // Check 5: File permissions
        if ($this->securityChecks['file_permissions'] ?? true) {
            $issues = array_merge($issues, $this->checkFilePermissions($plugin->getPath()));
        }

        $criticalCount = count(array_filter($issues, fn($i) => $i['severity'] === self::SEVERITY_CRITICAL));
        $highCount = count(array_filter($issues, fn($i) => $i['severity'] === self::SEVERITY_HIGH));

        $this->logger->info("Security validation completed", [
            'plugin' => $plugin->getName(),
            'total_issues' => count($issues),
            'critical' => $criticalCount,
            'high' => $highCount,
        ]);

        return $issues;
    }

    /**
     * Scan for dangerous PHP functions.
     * @throws Exception
     */
    public function scanForDangerousFunctions(string $pluginPath): array
    {
        $issues = [];

        foreach ($this->scanPhpFiles($pluginPath) as $file) {
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($this->dangerousFunctions as $function) {
                $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/';

                foreach ($lines as $lineNumber => $line) {
                    // Skip comments
                    if (preg_match('/^\s*(\/\/|#|\*)/', $line)) {
                        continue;
                    }

                    if (preg_match($pattern, $line)) {
                        $issues[] = [
                            'type' => 'dangerous_function',
                            'severity' => $this->getFunctionSeverity($function),
                            'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                            'line' => $lineNumber + 1,
                            'message' => sprintf("Use of dangerous function '%s()' detected", $function),
                            'suggestion' => $this->getSuggestionForFunction($function),
                            'code_snippet' => trim($line),
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Scan for path traversal attempts.
     * @throws Exception
     */
    public function scanForPathTraversal(string $pluginPath): array
    {
        $issues = [];

        $patterns = [
            '/\.\.[\/\\\\]/' => 'Path traversal pattern "../" detected',
            '/\$_(?:GET|POST|REQUEST|COOKIE)\[[\'"].*[\'"]]\s*\.\s*[\'"][\/\\\\]/' => 'User input used in file path without sanitization',
        ];

        foreach ($this->scanPhpFiles($pluginPath) as $file) {
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($patterns as $pattern => $message) {
                foreach ($lines as $lineNumber => $line) {
                    if (preg_match($pattern, $line)) {
                        $issues[] = [
                            'type' => 'path_traversal',
                            'severity' => self::SEVERITY_HIGH,
                            'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                            'line' => $lineNumber + 1,
                            'message' => $message,
                            'suggestion' => 'Use realpath() or basename() to sanitize file paths',
                            'code_snippet' => trim($line),
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Analyze database queries for potential SQL injection.
     * @throws Exception
     */
    public function analyzeDatabaseQueries(string $pluginPath): array
    {
        $issues = [];

        $patterns = [
            '/->query\([^\?].*\$/' => 'Direct variable interpolation in SQL query',
            '/execute\([\'"](?:SELECT|INSERT|UPDATE|DELETE).*\$/' => 'Variable concatenation in SQL query',
            '/\$[a-zA-Z_]+\s*\.\s*[\'"](?:SELECT|INSERT|UPDATE|DELETE)/' => 'SQL query built with string concatenation',
        ];

        foreach ($this->scanPhpFiles($pluginPath) as $file) {
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($patterns as $pattern => $message) {
                foreach ($lines as $lineNumber => $line) {
                    // Skip if using prepared statements (safe)
                    if (preg_match('/prepare|setParameter|bindValue|bindParam/', $line)) {
                        continue;
                    }

                    if (preg_match($pattern, $line)) {
                        $issues[] = [
                            'type' => 'sql_injection',
                            'severity' => self::SEVERITY_CRITICAL,
                            'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                            'line' => $lineNumber + 1,
                            'message' => $message,
                            'suggestion' => 'Use prepared statements with parameter binding',
                            'code_snippet' => trim($line),
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Scan for XSS vulnerability patterns.
     * @throws Exception
     */
    public function scanForXSSPatterns(string $pluginPath): array
    {
        $issues = [];

        $patterns = [
            '/echo\s+\$_(?:GET|POST|REQUEST|COOKIE)/' => 'Direct output of user input without escaping',
            '/print\s+\$_(?:GET|POST|REQUEST|COOKIE)/' => 'Direct output of user input without escaping',
            '/<\?=\s*\$_(?:GET|POST|REQUEST|COOKIE)/' => 'Direct output of user input in short tag',
        ];

        foreach ($this->scanPhpFiles($pluginPath) as $file) {
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($patterns as $pattern => $message) {
                foreach ($lines as $lineNumber => $line) {
                    // Skip if using escaping functions (safe)
                    if (preg_match('/htmlspecialchars|htmlentities|escape|e\(/', $line)) {
                        continue;
                    }

                    if (preg_match($pattern, $line)) {
                        $issues[] = [
                            'type' => 'xss',
                            'severity' => self::SEVERITY_HIGH,
                            'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                            'line' => $lineNumber + 1,
                            'message' => $message,
                            'suggestion' => 'Use htmlspecialchars() or Twig auto-escaping',
                            'code_snippet' => trim($line),
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check file permissions.
     * @throws Exception
     */
    public function checkFilePermissions(string $pluginPath): array
    {
        $issues = [];

        foreach ($this->scanPhpFiles($pluginPath) as $file) {
            $perms = fileperms($file->getRealPath());
            $octal = substr(sprintf('%o', $perms), -4);

            // Check if file is world-writable (dangerous)
            if (($perms & 0x0002) !== 0) {
                $issues[] = [
                    'type' => 'file_permissions',
                    'severity' => self::SEVERITY_HIGH,
                    'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                    'line' => 0,
                    'message' => sprintf("File is world-writable (permissions: %s)", $octal),
                    'suggestion' => 'Set file permissions to 644 or 640',
                    'code_snippet' => '',
                ];
            }

            // Check if file is executable (suspicious for .php files)
            if (($perms & 0x0040) !== 0) {
                $issues[] = [
                    'type' => 'file_permissions',
                    'severity' => self::SEVERITY_LOW,
                    'file' => $this->getRelativePath($file->getRealPath(), $pluginPath),
                    'line' => 0,
                    'message' => sprintf("PHP file is executable (permissions: %s)", $octal),
                    'suggestion' => 'PHP files should not be executable, set to 644',
                    'code_snippet' => '',
                ];
            }
        }

        return $issues;
    }

    /**
     * Get PHP files from plugin directory.
     * @throws Exception
     */
    private function scanPhpFiles(string $directory): Iterator
    {
        if (!is_dir($directory)) {
            return new ArrayIterator([]);
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('*.php')
            ->exclude(['vendor', 'node_modules', 'tests'])
            ->ignoreVCS(true);

        return $finder->getIterator();
    }

    /**
     * Get relative path for display.
     */
    private function getRelativePath(string $fullPath, string $basePath): string
    {
        if (str_starts_with($fullPath, $basePath)) {
            return substr($fullPath, strlen($basePath) + 1);
        }

        return $fullPath;
    }

    /**
     * Get severity level for dangerous function.
     */
    private function getFunctionSeverity(string $function): string
    {
        return match ($function) {
            'eval', 'exec', 'shell_exec', 'system', 'passthru' => self::SEVERITY_CRITICAL,
            'proc_open', 'popen', 'pcntl_exec' => self::SEVERITY_HIGH,
            default => self::SEVERITY_MEDIUM,
        };
    }

    /**
     * Get suggestion for dangerous function.
     */
    private function getSuggestionForFunction(string $function): string
    {
        return match ($function) {
            'eval' => 'Avoid eval(). Consider using safer alternatives or refactoring code',
            'exec', 'shell_exec', 'system', 'passthru' => 'Avoid shell commands. Use Symfony Process component with proper input validation',
            'proc_open', 'popen' => 'Use Symfony Process component instead',
            'assert' => 'Do not use assert() with string arguments in production',
            default => 'Consider using safer alternatives',
        };
    }
}
