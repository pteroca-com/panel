<?php

namespace App\Core\DTO;

use App\Core\Entity\Plugin;
use DateTimeImmutable;

/**
 * Data Transfer Object for plugin security scan results.
 *
 * Contains results of all security checks performed on a plugin:
 * - Dangerous functions detection
 * - Path traversal vulnerabilities
 * - SQL injection risks
 * - XSS (Cross-Site Scripting) patterns
 * - File permissions issues
 */
readonly class PluginSecurityCheckResultDTO
{
    /**
     * @param Plugin $plugin Plugin that was scanned
     * @param bool $secure Overall security status (true if no issues found)
     * @param array<string, bool> $checks Individual check results (check_name => passed)
     * @param array<int, array> $issues Detailed security issues found
     * @param DateTimeImmutable $scannedAt When the security scan was performed
     */
    public function __construct(
        public Plugin $plugin,
        public bool $secure,
        public array $checks,
        public array $issues,
        public DateTimeImmutable $scannedAt,
    ) {}

    /**
     * Create secure scan result (no issues found).
     *
     * @param Plugin $plugin Plugin that passed all security checks
     * @param array<string, bool> $checks Individual check results (all should be true)
     * @return self
     */
    public static function secure(Plugin $plugin, array $checks): self
    {
        return new self(
            plugin: $plugin,
            secure: true,
            checks: $checks,
            issues: [],
            scannedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Create insecure scan result (issues found).
     *
     * @param Plugin $plugin Plugin that has security issues
     * @param array<string, bool> $checks Individual check results
     * @param array<int, array> $issues Detailed security issues
     * @return self
     */
    public static function insecure(Plugin $plugin, array $checks, array $issues): self
    {
        return new self(
            plugin: $plugin,
            secure: false,
            checks: $checks,
            issues: $issues,
            scannedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Get count of passed checks.
     */
    public function getPassedCount(): int
    {
        return count(array_filter($this->checks, fn($passed) => $passed === true));
    }

    /**
     * Get count of failed checks.
     */
    public function getFailedCount(): int
    {
        return count(array_filter($this->checks, fn($passed) => $passed === false));
    }

    /**
     * Get total count of checks.
     */
    public function getTotalCount(): int
    {
        return count($this->checks);
    }

    /**
     * Get total number of security issues found.
     */
    public function getTotalIssues(): int
    {
        return count($this->issues);
    }

    /**
     * Get security score percentage (0-100).
     */
    public function getSecurityPercentage(): float
    {
        if ($this->getTotalCount() === 0) {
            return 100.0;
        }

        return ($this->getPassedCount() / $this->getTotalCount()) * 100;
    }

    /**
     * Get list of failed check names.
     *
     * @return array<string>
     */
    public function getFailedChecks(): array
    {
        return array_keys(array_filter($this->checks, fn($passed) => $passed === false));
    }

    /**
     * Check if specific security check passed.
     */
    public function checkPassed(string $checkName): bool
    {
        return $this->checks[$checkName] ?? false;
    }

    /**
     * Get issues for specific check type.
     *
     * @param string $checkType Check type (dangerous_functions, path_traversal, etc.)
     * @return array<int, array>
     */
    public function getIssuesByType(string $checkType): array
    {
        return array_filter($this->issues, fn($issue) => $issue['type'] === $checkType);
    }

    /**
     * Get count of issues for specific check type.
     */
    public function getIssueCountByType(string $checkType): int
    {
        return count($this->getIssuesByType($checkType));
    }

    /**
     * Get issues grouped by severity.
     *
     * @return array<string, array>
     */
    public function getIssuesBySeverity(): array
    {
        $grouped = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($this->issues as $issue) {
            $severity = $issue['severity'] ?? 'low';
            $grouped[$severity][] = $issue;
        }

        return $grouped;
    }

    /**
     * Get count of critical issues.
     */
    public function getCriticalCount(): int
    {
        return count(array_filter($this->issues, fn($issue) => ($issue['severity'] ?? '') === 'critical'));
    }

    /**
     * Get count of high severity issues.
     */
    public function getHighCount(): int
    {
        return count(array_filter($this->issues, fn($issue) => ($issue['severity'] ?? '') === 'high'));
    }
}
