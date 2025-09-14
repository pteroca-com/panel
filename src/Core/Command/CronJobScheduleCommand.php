<?php

namespace App\Core\Command;

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
        'app:delete-inactive-servers' => [
            [
                'settingName' => SettingEnum::DELETE_SUSPENDED_SERVERS_ENABLED,
                'settingValue' => '1',
            ]
        ],
        'app:delete-old-logs' => [
            [
                'settingName' => SettingEnum::LOG_CLEANUP_ENABLED,
                'settingValue' => '1',
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
        foreach (self::SCHEDULED_COMMANDS as $key => $scheduledCommand) {
            $requiredSettings = null;
            if (is_array($scheduledCommand)) {
                $requiredSettings = $scheduledCommand;
                $scheduledCommand = $key;
            }

            try {
                if ($requiredSettings) {
                    $this->checkRequiredSettings($scheduledCommand, $requiredSettings);
                }

                $io->writeln(sprintf('Executing command: %s', $scheduledCommand));
                $application->find($scheduledCommand)->run($input, $output);
            } catch (DisabledCommandException $e) {
                $io->info($e->getMessage());
            } catch (\Exception $e) {
                $io->error(sprintf('Error executing command: %s', $scheduledCommand));
                $io->error($e->getMessage());
            }
        }

        $io->success('Cron job has executed all scheduled commands.');
        return Command::SUCCESS;
    }

    private function checkRequiredSettings(string $scheduledCommand, array $requiredSettings): void
    {
        foreach ($requiredSettings as $requiredSetting) {
            $setting = $this->settingRepository->getSetting($requiredSetting['settingName']);

            if (empty($setting) || $setting !== $requiredSetting['settingValue']) {
                $message = sprintf(
                    'Command %s is omitted because required setting %s is not set to %s',
                    $scheduledCommand, $requiredSetting['settingName']->value, $requiredSetting['settingValue']
                );

                throw new DisabledCommandException($message);
            }
        }
    }
}
