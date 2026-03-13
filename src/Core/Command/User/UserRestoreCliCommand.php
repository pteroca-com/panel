<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\RestoreUserCommand;
use App\Core\Handler\User\RestoreUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:restore',
    description: 'Restore a soft-deleted user',
)]
class UserRestoreCliCommand extends Command
{
    public function __construct(
        private readonly RestoreUserHandler $handler,
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
            $this->handler->handle(new RestoreUserCommand(
                $input->getArgument('email'),
            ));
            $io->success('User restored successfully.');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
