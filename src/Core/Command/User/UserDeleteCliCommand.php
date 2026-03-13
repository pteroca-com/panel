<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\DeleteUserCommand;
use App\Core\Handler\User\DeleteUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:delete',
    description: 'Soft delete a user',
)]
class UserDeleteCliCommand extends Command
{
    public function __construct(
        private readonly DeleteUserHandler $handler,
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
        $email = $input->getArgument('email');

        if (!$io->confirm("Are you sure you want to delete user '{$email}'?", false)) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            $this->handler->handle(new DeleteUserCommand($email));
            $io->success("User '{$email}' has been deleted.");
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
