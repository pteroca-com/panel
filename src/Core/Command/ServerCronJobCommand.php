<?php

namespace App\Core\Command;

use App\Core\Handler\ServerCronJobHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:server-cron-job',
    description: 'Server cron job command',
)]
class ServerCronJobCommand extends Command
{
    public function __construct(
        private readonly ServerCronJobHandler $serverCronJobHandler
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->serverCronJobHandler->handle();
        $io->success('Server cron job executed successfully');

        return Command::SUCCESS;
    }
}
