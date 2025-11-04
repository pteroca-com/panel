<?php

namespace App\Core\Contract\Plugin;

use Exception;

/**
 * Interface for plugin cron tasks.
 *
 * Plugins that declare the 'cron' capability can implement this interface
 * to register scheduled tasks that run periodically.
 *
 * Example usage:
 *
 * ```php
 * namespace Plugins\MyPlugin\CronTask;
 *
 * use App\Core\Contract\Plugin\PluginCronTaskInterface;
 *
 * class DatabaseCleanupTask implements PluginCronTaskInterface
 * {
 *     public function execute(): void
 *     {
 *         // Your task logic here
 *     }
 *
 *     public function getSchedule(): string
 *     {
 *         return '0 2 * * *'; // Run daily at 2 AM
 *     }
 *
 *     public function getName(): string
 *     {
 *         return 'my-plugin:database-cleanup';
 *     }
 * }
 * ```
 */
interface PluginCronTaskInterface
{
    /**
     * Execute the cron task.
     *
     * This method contains the actual task logic that will be executed
     * according to the schedule.
     *
     * @throws Exception If task execution fails
     */
    public function execute(): void;

    /**
     * Get the cron schedule expression.
     *
     * Returns a standard cron expression that defines when the task should run.
     * Format: "minute hour day month day-of-week"
     *
     * Examples:
     * - "* * * * *" - Every minute
     * - "0 * * * *" - Every hour
     * - "0 0 * * *" - Daily at midnight
     * - "0 2 * * *" - Daily at 2 AM
     * - "0 0 * * 0" - Weekly on Sunday at midnight
     * - "0 0 1 * *" - Monthly on the 1st at midnight
     *
     * @return string Cron expression
     */
    public function getSchedule(): string;

    /**
     * Get the unique name/identifier for this task.
     *
     * This should be unique across all plugins and follow the format:
     * "plugin-name:task-name" (e.g., "my-plugin:cleanup-task")
     *
     * @return string Task identifier
     */
    public function getName(): string;

    /**
     * Get a human-readable description of this task.
     *
     * This description may be displayed in admin panels or logs.
     *
     * @return string Task description
     */
    public function getDescription(): string;

    /**
     * Check if the task is enabled.
     *
     * This allows tasks to be conditionally disabled based on configuration
     * or other runtime conditions.
     *
     * @return bool True if task should run, false otherwise
     */
    public function isEnabled(): bool;
}
