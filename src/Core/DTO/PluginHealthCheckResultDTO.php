<?php

namespace App\Core\DTO;

use App\Core\Entity\Plugin;
use DateTimeImmutable;

/**
 * Data Transfer Object for plugin health check results.
 *
 * Contains results of all health checks performed on a plugin:
 * - Files integrity
 * - Dependencies validation
 * - Configuration validation
 * - Database schema validation
 * - Service registration validation
 */
readonly class PluginHealthCheckResultDTO
{
    /**
     * @param Plugin $plugin Plugin that was checked
     * @param bool $healthy Overall health status (true if all checks passed)
     * @param array<string, bool> $checks Individual check results (check_name => passed)
     * @param array<string, string> $errors Error messages for failed checks (check_name => error_message)
     * @param DateTimeImmutable $checkedAt When the health check was performed
     */
    public function __construct(
        public Plugin $plugin,
        public bool $healthy,
        public array $checks,
        public array $errors,
        public DateTimeImmutable $checkedAt,
    ) {}

    /**
     * Create successful health check result.
     *
     * @param Plugin $plugin Plugin that passed all checks
     * @param array<string, bool> $checks Individual check results (all should be true)
     * @return self
     */
    public static function success(Plugin $plugin, array $checks): self
    {
        return new self(
            plugin: $plugin,
            healthy: true,
            checks: $checks,
            errors: [],
            checkedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Create failed health check result.
     *
     * @param Plugin $plugin Plugin that failed some checks
     * @param array<string, bool> $checks Individual check results
     * @param array<string, string> $errors Error messages for failed checks
     * @return self
     */
    public static function failure(Plugin $plugin, array $checks, array $errors): self
    {
        return new self(
            plugin: $plugin,
            healthy: false,
            checks: $checks,
            errors: $errors,
            checkedAt: new DateTimeImmutable(),
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
     * Get health percentage (0-100).
     */
    public function getHealthPercentage(): float
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
     * Check if specific check passed.
     */
    public function checkPassed(string $checkName): bool
    {
        return $this->checks[$checkName] ?? false;
    }

    /**
     * Get error message for specific check.
     */
    public function getError(string $checkName): ?string
    {
        return $this->errors[$checkName] ?? null;
    }
}
