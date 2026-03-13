<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\ListUsersCommand;
use App\Core\Entity\User;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;

class ListUsersHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @return array<User>
     */
    public function handle(object $command): array
    {
        assert($command instanceof ListUsersCommand);

        if ($command->deleted === true) {
            $users = $this->userRepository->createQueryBuilder('u')
                ->where('u.deletedAt IS NOT NULL')
                ->getQuery()
                ->getResult();
        } elseif ($command->deleted === false) {
            $users = $this->userRepository->findAllNotDeleted();
        } else {
            $users = $this->userRepository->createQueryBuilder('u')
                ->getQuery()
                ->getResult();
        }

        if ($command->blocked !== null) {
            $users = array_filter($users, fn(User $user) => $user->isBlocked() === $command->blocked);
        }

        if ($command->role !== null) {
            $users = array_filter($users, fn(User $user) => in_array($command->role, $user->getRoles()));
        }

        return array_values($users);
    }
}
