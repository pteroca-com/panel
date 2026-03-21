<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\ChangeUserBalanceCommand;
use App\Core\Handler\User\ChangeUserBalanceHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:change-balance',
    description: 'Change user balance',
)]
class UserChangeBalanceCliCommand extends Command
{
    public function __construct(
        private readonly ChangeUserBalanceHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to change')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'Mode: set, add, subtract', 'set')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mode = $input->getOption('mode');

        if (!in_array($mode, ['set', 'add', 'subtract'])) {
            $io->error("Invalid mode: '{$mode}'. Use 'set', 'add', or 'subtract'.");
            return Command::FAILURE;
        }

        try {
            $this->handler->handle(new ChangeUserBalanceCommand(
                $input->getArgument('email'),
                (float) $input->getArgument('amount'),
                $mode,
            ));
            $io->success('User balance changed successfully.');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
