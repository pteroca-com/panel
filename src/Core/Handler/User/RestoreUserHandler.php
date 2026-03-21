<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\RestoreUserCommand;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\User\UserManagementService;

class RestoreUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof RestoreUserCommand);

        $user = $this->userRepository->findDeletedByEmail($command->email);
        if (!$user) {
            throw new \RuntimeException("Deleted user not found: {$command->email}");
        }

        $this->userManagementService->restoreUser($user);

        return null;
    }
}
