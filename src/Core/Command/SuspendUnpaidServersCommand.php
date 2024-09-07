<?php

namespace App\Core\Command;

use App\Core\Handler\SuspendUnpaidServersHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:suspend-unpaid-servers',
    description: 'Suspend unpaid servers',
)]
class SuspendUnpaidServersCommand extends Command
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
