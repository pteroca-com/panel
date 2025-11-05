<?php

namespace App\Core\Service;

use App\Core\Contract\Plugin\PluginCronTaskInterface;
use App\Core\Service\Plugin\PluginCronRegistry;
use Cron\CronExpression;
use DateTime;
use DateTimeInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Cron scheduler service for executing plugin scheduled tasks.
 *
 * Uses CronExpression library to determine when tasks should run
 * and executes them accordingly.
 */
class CronScheduler
{
    /** @var int Minutes between summary logs when no tasks are due */
    private const SUMMARY_LOG_INTERVAL_MINUTES = 15;

    /** @var DateTime|null Last time a summary was logged */
    private ?DateTime $lastSummaryLogTime = null;

    public function __construct(
        private readonly PluginCronRegistry $cronRegistry,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Run all due tasks based on current time.
     *
     * @param DateTimeInterface|null $currentTime Time to check against (defaults to now)
     * @return array<string, array> Results of task execution indexed by task name
     */
    public function runDueTasks(?DateTimeInterface $currentTime = null): array
    {
        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $results = [];
        $enabledTasks = $this->cronRegistry->getEnabledTasks();

        // Check if there are due tasks to determine if we should log
        $dueTasks = $this->getDueTasksInternal($currentTime, $enabledTasks);
        $shouldLog = !empty($dueTasks) || $this->shouldLogSummary($currentTime);

        // Log start only when there are due tasks or it's time for periodic summary
        if ($shouldLog) {
            $this->logger->info('Cron scheduler started', [
                'time' => $currentTime->format('Y-m-d H:i:s'),
                'total_tasks' => count($enabledTasks),
                'due_tasks' => count($dueTasks),
            ]);
        }

        foreach ($enabledTasks as $task) {
            $taskName = $task->getName();

            try {
                if ($this->shouldRun($task, $currentTime)) {
                    $results[$taskName] = $this->executeTask($task, $currentTime);
                } else {
                    $results[$taskName] = [
                        'status' => 'skipped',
                        'reason' => 'Not due yet',
                        'next_run' => $this->getNextRunTime($task, $currentTime)->format('Y-m-d H:i:s'),
                    ];
                }
            } catch (Exception $e) {
                $results[$taskName] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ];

                $this->logger->error('Failed to process cron task', [
                    'task' => $taskName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log finish only when we logged start
        if ($shouldLog) {
            $this->logger->info('Cron scheduler finished', [
                'time' => $currentTime->format('Y-m-d H:i:s'),
                'executed' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
                'failed' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
                'skipped' => count(array_filter($results, fn($r) => $r['status'] === 'skipped')),
            ]);

            // Update last summary log time
            $this->lastSummaryLogTime = DateTime::createFromInterface($currentTime);
        }

        return $results;
    }

    /**
     * Execute a specific task by name.
     *
     * @param string $taskName Task name (format: "plugin-name:task-name")
     * @param DateTimeInterface|null $currentTime Current time for logging
     * @return array Result of task execution
     */
    public function runTask(string $taskName, ?DateTimeInterface $currentTime = null): array
    {
        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $task = $this->cronRegistry->getTask($taskName);

        if ($task === null) {
            $this->logger->error('Task not found', [
                'task' => $taskName,
            ]);
            return [
                'status' => 'error',
                'error' => 'Task not found',
            ];
        }

        if (!$task->isEnabled()) {
            $this->logger->warning('Task is disabled', [
                'task' => $taskName,
            ]);
            return [
                'status' => 'error',
                'error' => 'Task is disabled',
            ];
        }

        return $this->executeTask($task, $currentTime);
    }

    /**
     * Check if a task should run at the given time.
     *
     * @param PluginCronTaskInterface $task Task to check
     * @param DateTimeInterface $time Time to check against
     * @return bool True if task is due, false otherwise
     */
    public function shouldRun(PluginCronTaskInterface $task, DateTimeInterface $time): bool
    {
        try {
            $cron = new CronExpression($task->getSchedule());
            return $cron->isDue($time);
        } catch (Exception $e) {
            $this->logger->error('Invalid cron expression', [
                'task' => $task->getName(),
                'schedule' => $task->getSchedule(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the next run time for a task.
     *
     * @param PluginCronTaskInterface $task Task to check
     * @param DateTimeInterface|null $currentTime Current time (defaults to now)
     * @return DateTimeInterface Next run time
     * @throws Exception If cron expression is invalid
     */
    public function getNextRunTime(PluginCronTaskInterface $task, ?DateTimeInterface $currentTime = null): DateTimeInterface
    {
        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $cron = new CronExpression($task->getSchedule());
        return $cron->getNextRunDate($currentTime);
    }

    /**
     * Get the previous run time for a task.
     *
     * @param PluginCronTaskInterface $task Task to check
     * @param DateTimeInterface|null $currentTime Current time (defaults to now)
     * @return DateTimeInterface Previous run time
     * @throws Exception If cron expression is invalid
     */
    public function getPreviousRunTime(PluginCronTaskInterface $task, ?DateTimeInterface $currentTime = null): DateTimeInterface
    {
        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $cron = new CronExpression($task->getSchedule());
        return $cron->getPreviousRunDate($currentTime);
    }

    /**
     * Get all due tasks at the given time.
     *
     * @param DateTimeInterface|null $currentTime Time to check against (defaults to now)
     * @return PluginCronTaskInterface[]
     */
    public function getDueTasks(?DateTimeInterface $currentTime = null): array
    {
        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $enabledTasks = $this->cronRegistry->getEnabledTasks();
        $dueTasks = [];

        foreach ($enabledTasks as $task) {
            if ($this->shouldRun($task, $currentTime)) {
                $dueTasks[] = $task;
            }
        }

        return $dueTasks;
    }

    /**
     * Validate a cron expression.
     *
     * @param string $expression Cron expression to validate
     * @return bool True if valid, false otherwise
     */
    public function validateCronExpression(string $expression): bool
    {
        try {
            new CronExpression($expression);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get human-readable description of a cron expression.
     *
     * @param string $expression Cron expression
     * @return string Human-readable description
     */
    public function describeCronExpression(string $expression): string
    {
        try {
            $cron = new CronExpression($expression);

            // Get next few run times to help describe the pattern
            $now = new DateTime();
            $next1 = $cron->getNextRunDate($now);
            $next2 = $cron->getNextRunDate($next1);
            $next3 = $cron->getNextRunDate($next2);

            // Calculate interval between runs
            $interval = $next1->diff($next2);

            $description = sprintf(
                'Runs at %s (next: %s, then: %s)',
                $next1->format('Y-m-d H:i'),
                $next2->format('Y-m-d H:i'),
                $next3->format('Y-m-d H:i')
            );

            // Try to describe the pattern
            if ($interval->days === 0 && $interval->h === 0 && $interval->i === 1) {
                return 'Every minute';
            } elseif ($interval->days === 0 && $interval->h === 1 && $interval->i === 0) {
                return 'Every hour';
            } elseif ($interval->days === 1 && $interval->h === 0) {
                return sprintf('Daily at %s', $next1->format('H:i'));
            } elseif ($interval->days === 7) {
                return sprintf('Weekly on %s at %s', $next1->format('l'), $next1->format('H:i'));
            }

            return $description;
        } catch (Exception) {
            return 'Invalid cron expression';
        }
    }

    /**
     * Check if summary should be logged (every N minutes when no tasks are due).
     *
     * @param DateTimeInterface $currentTime Current time
     * @return bool True if summary should be logged
     */
    private function shouldLogSummary(DateTimeInterface $currentTime): bool
    {
        if ($this->lastSummaryLogTime === null) {
            return true; // First run - always log
        }

        $minutesSinceLastLog = ($currentTime->getTimestamp() - $this->lastSummaryLogTime->getTimestamp()) / 60;

        return $minutesSinceLastLog >= self::SUMMARY_LOG_INTERVAL_MINUTES;
    }

    /**
     * Get due tasks without logging (internal helper).
     *
     * @param DateTimeInterface $currentTime Time to check against
     * @param PluginCronTaskInterface[] $enabledTasks Tasks to check
     * @return PluginCronTaskInterface[] Due tasks
     */
    private function getDueTasksInternal(DateTimeInterface $currentTime, array $enabledTasks): array
    {
        $dueTasks = [];
        foreach ($enabledTasks as $task) {
            if ($this->shouldRun($task, $currentTime)) {
                $dueTasks[] = $task;
            }
        }
        return $dueTasks;
    }

    /**
     * Execute a single task.
     *
     * @param PluginCronTaskInterface $task Task to execute
     * @param DateTimeInterface $currentTime Current time for logging
     * @return array Execution result
     */
    private function executeTask(PluginCronTaskInterface $task, DateTimeInterface $currentTime): array
    {
        $taskName = $task->getName();
        $startTime = microtime(true);

        $this->logger->info('Executing cron task', [
            'task' => $taskName,
            'schedule' => $task->getSchedule(),
            'description' => $task->getDescription(),
            'time' => $currentTime->format('Y-m-d H:i:s'),
        ]);

        try {
            $task->execute();

            $duration = microtime(true) - $startTime;

            $this->logger->info('Cron task completed successfully', [
                'task' => $taskName,
                'duration' => round($duration, 3) . 's',
            ]);

            return [
                'status' => 'success',
                'duration' => round($duration, 3),
                'executed_at' => $currentTime->format('Y-m-d H:i:s'),
                'next_run' => $this->getNextRunTime($task, $currentTime)->format('Y-m-d H:i:s'),
            ];

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Cron task failed', [
                'task' => $taskName,
                'duration' => round($duration, 3) . 's',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'duration' => round($duration, 3),
                'executed_at' => $currentTime->format('Y-m-d H:i:s'),
            ];
        }
    }
}
