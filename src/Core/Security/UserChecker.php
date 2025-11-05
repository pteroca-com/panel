<?php

namespace App\Core\Security;

use App\Core\Contract\UserInterface as AppUserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUserInterface) {
            return;
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('pteroca.login.account_deleted')
            );
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('pteroca.login.user_blocked')
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No additional checks needed after authentication
    }
}
