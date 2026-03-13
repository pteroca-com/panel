<?php

namespace App\Core\Service\User;

use App\Core\Entity\User;
use App\Core\Repository\UserRepository;

readonly class UserManagementService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function blockUser(User $user): void
    {
        $user->setIsBlocked(true);
        $this->userRepository->save($user);
    }

    public function unblockUser(User $user): void
    {
        $user->setIsBlocked(false);
        $this->userRepository->save($user);
    }

    public function verifyUser(User $user): void
    {
        $user->setIsVerified(true);
        $this->userRepository->save($user);
    }

    public function softDeleteUser(User $user): void
    {
        $user->softDelete();
        $this->userRepository->save($user);
    }

    public function restoreUser(User $user): void
    {
        $user->restore();
        $this->userRepository->save($user);
    }

    public function changeBalance(User $user, float $amount, string $mode): void
    {
        $newBalance = match ($mode) {
            'add' => $user->getBalance() + $amount,
            'subtract' => $user->getBalance() - $amount,
            'set' => $amount,
            default => throw new \RuntimeException("Invalid mode: {$mode}. Use 'add', 'subtract', or 'set'."),
        };

        if ($newBalance < 0) {
            throw new \RuntimeException('Balance cannot be negative.');
        }

        $user->setBalance($newBalance);
        $this->userRepository->save($user);
    }
}
