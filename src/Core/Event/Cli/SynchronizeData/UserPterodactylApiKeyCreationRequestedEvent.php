<?php

namespace App\Core\Event\Cli\SynchronizeData;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class UserPterodactylApiKeyCreationRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly string $userEmail,
        private readonly string $userName,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
