<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\ListUsersCommand;
use App\Core\Entity\User;
use App\Core\Handler\User\ListUsersHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:list',
    description: 'List all users',
)]
class UserListCliCommand extends Command
{
    public function __construct(
        private readonly ListUsersHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Filter by role name')
            ->addOption('blocked', null, InputOption::VALUE_NONE, 'Show only blocked users')
            ->addOption('deleted', null, InputOption::VALUE_NONE, 'Show only deleted users')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $blocked = $input->getOption('blocked') ? true : null;
        $deleted = $input->getOption('deleted') ? true : null;

        $users = $this->handler->handle(new ListUsersCommand(
            role: $input->getOption('role'),
            blocked: $blocked,
            deleted: $deleted,
        ));

        if (empty($users)) {
            $io->info('No users found.');
            return Command::SUCCESS;
        }

        $rows = array_map(fn(User $user) => [
            $user->getId(),
            $user->getEmail(),
            $user->getName() . ' ' . $user->getSurname(),
            implode(', ', $user->getRoles()),
            $user->isVerified() ? 'Yes' : 'No',
            $user->isBlocked() ? 'Yes' : 'No',
            $user->isDeleted() ? 'Yes' : 'No',
        ], $users);

        $io->table(
            ['ID', 'Email', 'Name', 'Roles', 'Verified', 'Blocked', 'Deleted'],
            $rows,
        );

        $io->info(sprintf('Total: %d user(s)', count($users)));

        return Command::SUCCESS;
    }
}
