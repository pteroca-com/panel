<?php

namespace App\Core\Event\User\Account;

use App\Core\Entity\Panel\UserAccount;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class UserAccountUpdateRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly UserAccount $userAccount,
        private readonly ?string $plainPassword,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserAccount(): UserAccount
    {
        return $this->userAccount;
    }

    public function getUserId(): ?int
    {
        return $this->userAccount->getId();
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function hasPasswordChange(): bool
    {
        return $this->plainPassword !== null;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getEmail(): ?string
    {
        return $this->userAccount->getEmail();
    }
}
