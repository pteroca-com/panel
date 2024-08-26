<?php

namespace App\Core\Service\Server;

use App\Core\Entity\User;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;

abstract class AbstractActionServerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PterodactylService $pterodactylService,
    ) {}

    protected function updateUserBalance(User $user, int $price): void
    {
        $user->setBalance($user->getBalance() - $price);
        $this->userRepository->save($user);
    }

    protected function getPterodactylAccountLogin(User $user): ?string
    {
        return $this->pterodactylService->getApi()->users->get($user->getPterodactylUserId())?->username;
    }
}
