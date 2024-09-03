<?php

namespace App\Core\Command;

use App\Core\Handler\SuspendUnpaidServersHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-inactive-servers',
    description: 'Delete inactive servers',
)]
class DeleteInactiveServersCommand extends Command
{
    public function __construct(
        private readonly SuspendUnpaidServersHandler $suspendUnpaidServersHandler
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->suspendUnpaidServersHandler->handle();
        $io->success('Suspend unpaid servers command executed successfully');

        return Command::SUCCESS;
    }
}
