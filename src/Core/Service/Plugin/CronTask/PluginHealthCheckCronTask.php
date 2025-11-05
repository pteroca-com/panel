<?php

namespace App\Core\Service\Plugin\CronTask;

use App\Core\Contract\Plugin\PluginCronTaskInterface;
use App\Core\Service\Plugin\PluginHealthCheckService;
use Psr\Log\LoggerInterface;

/**
 * Automated health check cron task.
 * Runs every 6 hours to check all enabled plugins.
 */
readonly class PluginHealthCheckCronTask implements PluginCronTaskInterface
{
    public function __construct(
        private PluginHealthCheckService $healthCheckService,
        private LoggerInterface          $logger,
    ) {}

    public function getName(): string
    {
        return 'plugin_health_check';
    }

    public function getDescription(): string
    {
        return 'Check health of all enabled plugins';
    }

    public function getSchedule(): string
    {
        return '0 */6 * * *'; // Every 6 hours
    }

    public function execute(): void
    {
        $this->logger->info('Starting automated plugin health check');

        $results = $this->healthCheckService->runAllHealthChecks();

        $unhealthyPlugins = array_filter($results, fn($r) => !$r->healthy);

        if (!empty($unhealthyPlugins)) {
            foreach ($unhealthyPlugins as $result) {
                $this->logger->warning('Plugin health check failed', [
                    'plugin' => $result->plugin->getName(),
                    'errors' => $result->errors,
                    'health_percentage' => $result->getHealthPercentage(),
                ]);
            }
        }

        $this->logger->info('Automated plugin health check completed', [
            'total' => count($results),
            'healthy' => count($results) - count($unhealthyPlugins),
            'unhealthy' => count($unhealthyPlugins),
        ]);
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
