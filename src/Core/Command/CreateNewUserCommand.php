<?php

namespace App\Core\Command;

use App\Core\Handler\CreateNewUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-new-user',
    description: 'Tworzy nowego użytkownika w systemie',
)]
class CreateNewUserCommand extends Command
{
    public function __construct(
        private readonly CreateNewUserHandler $createNewUserHandler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $this->createNewUserHandler->setUserCredentials($email, $password);
        $this->createNewUserHandler->handle();

        $io->success('New user created!');

        return Command::SUCCESS;
    }
}
