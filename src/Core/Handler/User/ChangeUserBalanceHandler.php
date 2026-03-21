<?php

namespace App\Core\Handler\User;

use App\Core\DTO\Command\User\ChangeUserBalanceCommand;
use App\Core\Handler\CommandHandlerInterface;
use App\Core\Repository\UserRepository;
use App\Core\Service\User\UserManagementService;

class ChangeUserBalanceHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof ChangeUserBalanceCommand);

        $user = $this->userRepository->findOneBy(['email' => $command->email]);
        if (!$user) {
            throw new \RuntimeException("User not found: {$command->email}");
        }

        $this->userManagementService->changeBalance($user, $command->amount, $command->mode);

        return null;
    }
}
