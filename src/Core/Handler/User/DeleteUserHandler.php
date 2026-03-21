<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\DeleteUserCommand;
use App\Core\Exception\PterodactylUserNotFoundException;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\User\UserManagementService;
use App\Core\Service\User\UserService;

class DeleteUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagementService $userManagementService,
        private readonly UserService $userService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof DeleteUserCommand);

        $user = $this->userRepository->findOneBy(['email' => $command->email]);
        if (!$user) {
            throw new \RuntimeException("User not found: {$command->email}");
        }

        if ($user->isDeleted()) {
            throw new \RuntimeException("User is already deleted: {$command->email}");
        }

        // Delete from Pterodactyl if user has a Pterodactyl account
        if ($user->getPterodactylUserId()) {
            try {
                $this->userService->deleteUserFromPterodactyl($user);
            } catch (PterodactylUserNotFoundException) {
                // User not found in Pterodactyl, continue with soft delete
            }
        }

        $this->userManagementService->softDeleteUser($user);

        return null;
    }
}
