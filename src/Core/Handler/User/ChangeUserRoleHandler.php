<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\ChangeUserRoleCommand;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\Security\RoleManager;

class ChangeUserRoleHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleManager $roleManager,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof ChangeUserRoleCommand);

        $user = $this->userRepository->findOneBy(['email' => $command->email]);
        if (!$user) {
            throw new \RuntimeException("User not found: {$command->email}");
        }

        $role = $this->roleManager->getRoleByName($command->role);
        if (!$role) {
            throw new \RuntimeException("Role not found: {$command->role}");
        }

        $this->roleManager->assignRolesToUser($user, [$role]);

        return null;
    }
}
