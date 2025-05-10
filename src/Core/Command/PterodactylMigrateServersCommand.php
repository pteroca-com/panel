<?php

namespace App\Core\Command;

use App\Core\Handler\MigrateServersHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pterodactyl:migrate-servers',
    description: 'Migrate servers from Pterodactyl to existing user accounts',
)]
class PterodactylMigrateServersCommand extends Command
{
    public function __construct(
        private readonly MigrateServersHandler $migrateServersHandler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Limit the number of servers to migrate',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrateServersHandler
            ->setLimit($input->getOption('limit') ?: 100)
            ->setIo($io)
            ->handle();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
