<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\UnblockUserCommand;
use App\Core\Handler\User\UnblockUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:unblock',
    description: 'Unblock a user',
)]
class UserUnblockCliCommand extends Command
{
    public function __construct(
        private readonly UnblockUserHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->handler->handle(new UnblockUserCommand(
                $input->getArgument('email'),
            ));
            $io->success('User unblocked successfully.');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
