<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\ChangeUserPasswordCommand;
use App\Core\Handler\User\ChangeUserPasswordHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:change-password',
    description: 'Change user password',
    aliases: ['app:change-user-password']
)]
class UserChangePasswordCommand extends Command
{
    public function __construct(
        private readonly ChangeUserPasswordHandler $handler,
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

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->handler->handle(new ChangeUserPasswordCommand(
                $input->getArgument('email'),
                $input->getArgument('password'),
            ));
            $io->success('User password changed!');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
