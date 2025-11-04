<?php

namespace App\Core\Trait;

use App\Core\Contract\UserInterface;
use LogicException;

trait GetUserTrait
{
    protected function getUser(): ?UserInterface
    {
        $user = parent::getUser();
        if (empty($user)) {
            return null;
        }

        if (! $user instanceof UserInterface) {
            throw new LogicException(sprintf(
                'Expected instance of %s, got %s',
                UserInterface::class, get_class($user)
            ));
        }

        return $user;
    }

    protected function getUserId(): int
    {
        $user = $this->getUser();
        $userId = $user?->getId();

        if ($userId === null) {
            throw new LogicException('User ID cannot be null in authenticated context');
        }

        return $userId;
    }
}
