<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\VerifyUserCommand;
use App\Core\Handler\User\VerifyUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:verify',
    description: 'Verify a user',
)]
class UserVerifyCliCommand extends Command
{
    public function __construct(
        private readonly VerifyUserHandler $handler,
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
            $this->handler->handle(new VerifyUserCommand(
                $input->getArgument('email'),
            ));
            $io->success('User verified successfully.');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
