<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\GetUserInfoCommand;
use App\Core\Entity\User;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;

class GetUserInfoHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(object $command): User
    {
        assert($command instanceof GetUserInfoCommand);

        $identifier = $command->identifier;

        // Try to find by ID if numeric
        if (is_numeric($identifier)) {
            $user = $this->userRepository->createQueryBuilder('u')
                ->where('u.id = :id')
                ->setParameter('id', (int) $identifier)
                ->getQuery()
                ->getOneOrNullResult();

            if ($user) {
                return $user;
            }
        }

        // Try to find by email (including deleted)
        $user = $this->userRepository->findByEmailIncludingDeleted($identifier);

        if (!$user) {
            throw new \RuntimeException("User not found: {$identifier}");
        }

        return $user;
    }
}
