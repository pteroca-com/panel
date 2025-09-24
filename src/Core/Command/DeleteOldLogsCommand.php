<?php

namespace App\Core\Command;

use App\Core\Enum\SettingEnum;
use App\Core\Repository\EmailLogRepository;
use App\Core\Repository\LogRepository;
use App\Core\Repository\ServerLogRepository;
use App\Core\Repository\SettingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-old-logs',
    description: 'Delete old logs based on system settings',
)]
class DeleteOldLogsCommand extends Command
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly LogRepository $logRepository,
        private readonly ServerLogRepository $serverLogRepository,
        private readonly EmailLogRepository $emailLogRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $daysAfter = $this->settingRepository->getSetting(SettingEnum::LOG_CLEANUP_DAYS_AFTER);
        if (!$daysAfter || !is_numeric($daysAfter)) {
            $io->error('Invalid log cleanup days setting.');
            return Command::FAILURE;
        }

        $daysAfterInt = (int) $daysAfter;
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$daysAfterInt} days");

        $io->writeln(sprintf('Deleting logs older than %d days (before %s)', $daysAfterInt, $cutoffDate->format('Y-m-d H:i:s')));

        $totalDeleted = 0;

        $deletedLogs = $this->logRepository->deleteOldLogs($cutoffDate);
        $totalDeleted += $deletedLogs;
        $io->writeln(sprintf('Deleted %d old log entries', $deletedLogs));

        $deletedServerLogs = $this->serverLogRepository->deleteOldLogs($cutoffDate);
        $totalDeleted += $deletedServerLogs;
        $io->writeln(sprintf('Deleted %d old server log entries', $deletedServerLogs));

        $deletedEmailLogs = $this->emailLogRepository->deleteOldLogs($cutoffDate);
        $totalDeleted += $deletedEmailLogs;
        $io->writeln(sprintf('Deleted %d old email log entries', $deletedEmailLogs));

        $io->success(sprintf('Log cleanup completed. Total deleted entries: %d', $totalDeleted));

        return Command::SUCCESS;
    }
}
