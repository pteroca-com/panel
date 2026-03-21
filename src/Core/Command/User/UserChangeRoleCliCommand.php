<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\ChangeUserRoleCommand;
use App\Core\Handler\User\ChangeUserRoleHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:change-role',
    description: 'Change user role',
)]
class UserChangeRoleCliCommand extends Command
{
    public function __construct(
        private readonly ChangeUserRoleHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role name (e.g. ROLE_ADMIN)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->handler->handle(new ChangeUserRoleCommand(
                $input->getArgument('email'),
                $input->getArgument('role'),
            ));
            $io->success('User role changed successfully.');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
