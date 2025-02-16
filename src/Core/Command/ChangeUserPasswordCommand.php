<?php

namespace App\Core\Command;

use App\Core\Handler\ChangeUserPasswordHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:change-user-password',
    description: 'Change user password',
)]
class ChangeUserPasswordCommand extends Command
{
    public function __construct(
        private readonly ChangeUserPasswordHandler $changeUserPasswordHandler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'New password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $this->changeUserPasswordHandler->setUserCredentials($email, $password);
        $this->changeUserPasswordHandler->handle();

        $io->success('User password changed!');

        return Command::SUCCESS;
    }
}
