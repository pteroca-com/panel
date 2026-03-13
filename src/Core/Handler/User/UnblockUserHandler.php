<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\UnblockUserCommand;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\User\UserManagementService;

class UnblockUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof UnblockUserCommand);

        $user = $this->userRepository->findOneBy(['email' => $command->email]);
        if (!$user) {
            throw new \RuntimeException("User not found: {$command->email}");
        }

        if (!$user->isBlocked()) {
            throw new \RuntimeException("User is not blocked: {$command->email}");
        }

        $this->userManagementService->unblockUser($user);

        return null;
    }
}
