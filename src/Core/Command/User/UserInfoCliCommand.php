<?php

namespace App\Core\Command\User;

use App\Core\DTO\Command\User\GetUserInfoCommand;
use App\Core\Handler\User\GetUserInfoHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:user:info',
    description: 'Show detailed user information',
)]
class UserInfoCliCommand extends Command
{
    public function __construct(
        private readonly GetUserInfoHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('identifier', InputArgument::REQUIRED, 'User email or ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $user = $this->handler->handle(new GetUserInfoCommand(
                $input->getArgument('identifier'),
            ));
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->title('User Information');
        $io->definitionList(
            ['ID' => $user->getId()],
            ['Email' => $user->getEmail()],
            ['Name' => $user->getName()],
            ['Surname' => $user->getSurname()],
            ['Roles' => implode(', ', $user->getRoles())],
            ['Balance' => number_format($user->getBalance(), 2)],
            ['Verified' => $user->isVerified() ? 'Yes' : 'No'],
            ['Blocked' => $user->isBlocked() ? 'Yes' : 'No'],
            ['Deleted' => $user->isDeleted() ? 'Yes' : 'No'],
            ['Pterodactyl User ID' => $user->getPterodactylUserId() ?? 'N/A'],
            ['Created At' => $user->getCreatedAt()->format('Y-m-d H:i:s')],
            ['Updated At' => $user->getUpdatedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
            ['Deleted At' => $user->getDeletedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
        );

        return Command::SUCCESS;
    }
}
