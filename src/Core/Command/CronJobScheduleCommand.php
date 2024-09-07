<?php

namespace App\Core\Command;

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
    ];

    public function __construct(
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $application = $this->getApplication();
        foreach (self::SCHEDULED_COMMANDS as $scheduledCommand) {
            try {
                $io->writeln(sprintf('Executing command: %s', $scheduledCommand));
                $application->find($scheduledCommand)->run($input, $output);
            } catch (\Exception $e) {
                $io->error(sprintf('Error executing command: %s', $scheduledCommand));
                $io->error($e->getMessage());
            }
        }

        $io->success('Cron job has executed all scheduled commands.');
        return Command::SUCCESS;
    }
}
