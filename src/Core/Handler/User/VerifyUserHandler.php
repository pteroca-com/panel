<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\VerifyUserCommand;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\User\UserManagementService;

class VerifyUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof VerifyUserCommand);

        $user = $this->userRepository->findOneBy(['email' => $command->email]);
        if (!$user) {
            throw new \RuntimeException("User not found: {$command->email}");
        }

        if ($user->isVerified()) {
            throw new \RuntimeException("User is already verified: {$command->email}");
        }

        $this->userManagementService->verifyUser($user);

        return null;
    }
}
