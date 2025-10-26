<?php

namespace App\Core\Command;

use App\Core\Enum\CronIntervalEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Exception\DisabledCommandException;
use App\Core\Repository\SettingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cron-job-schedule',
    description: 'Cron job schedule command',
)]
class CronJobScheduleCommand extends Command
{
    private const SCHEDULED_COMMANDS = [
        'app:suspend-unpaid-servers',

        'app:cleanup-purchase-tokens' => [
            'interval' => CronIntervalEnum::HOURLY,
        ],

        'app:delete-inactive-servers' => [
            'interval' => CronIntervalEnum::DAILY,
            'conditions' => [
                [
                    'settingName' => SettingEnum::DELETE_SUSPENDED_SERVERS_ENABLED,
                    'settingValue' => '1',
                ]
            ]
        ],

        'app:delete-old-logs' => [
            'conditions' => [
                [
                    'settingName' => SettingEnum::LOG_CLEANUP_ENABLED,
                    'settingValue' => '1',
                ]
            ]
        ],
    ];

    public function __construct(
        private readonly SettingRepository $settingRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');

        $io->info(sprintf('[%s] Starting cron job execution', $timestamp));

        foreach (self::SCHEDULED_COMMANDS as $key => $config) {
            // Parse command configuration
            $commandName = is_string($config) ? $config : $key;
            $conditions = null;
            $interval = CronIntervalEnum::EVERY_MINUTE;

            if (is_array($config)) {
                $conditions = $config['conditions'] ?? null;
                $interval = $config['interval'] ?? CronIntervalEnum::EVERY_MINUTE;
            }

            // Check if command should execute now based on interval
            if (!$interval->shouldExecuteNow()) {
                $io->info(sprintf('⏭  Skipping command: %s (interval: %s)', $commandName, $interval->value));
                continue;
            }

            try {
                // Check required conditions (settings)
                if ($conditions) {
                    $this->checkConditions($commandName, $conditions);
                }

                $io->writeln(sprintf('▶  Executing command: %s (interval: %s)', $commandName, $interval->value));
                $application->find($commandName)->run($input, $output);
            } catch (DisabledCommandException $e) {
                $io->info($e->getMessage());
            } catch (\Exception $e) {
                $io->error(sprintf('❌ Error executing command: %s', $commandName));
                $io->error($e->getMessage());
            }
        }

        $io->success('Cron job has executed all scheduled commands.');
        return Command::SUCCESS;
    }

    private function checkConditions(string $commandName, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $setting = $this->settingRepository->getSetting($condition['settingName']);

            if (empty($setting) || $setting !== $condition['settingValue']) {
                $message = sprintf(
                    'Command %s is omitted because required setting %s is not set to %s',
                    $commandName,
                    $condition['settingName']->value,
                    $condition['settingValue']
                );

                throw new DisabledCommandException($message);
            }
        }
    }
}
