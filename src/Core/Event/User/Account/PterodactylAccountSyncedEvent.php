<?php

namespace App\Core\Event\User\Account;

use App\Core\Entity\Panel\UserAccount;
use App\Core\Event\AbstractDomainEvent;

class PterodactylAccountSyncedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly UserAccount $userAccount,
        private readonly int $pterodactylUserId,
        private readonly bool $passwordWasSynced,
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

    public function getPterodactylUserId(): int
    {
        return $this->pterodactylUserId;
    }

    public function wasPasswordSynced(): bool
    {
        return $this->passwordWasSynced;
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
