<?php

namespace App\Core\Event\User\Registration;

use App\Core\Event\AbstractDomainEvent;


class UserRegistrationFailedEvent extends AbstractDomainEvent
{

    public function __construct(
        private readonly string $email,
        private readonly string $reason,
        private readonly string $stage = 'unknown',
        private readonly array $context = [],
        private readonly ?string $eventId = null
    ) {
        parent::__construct($eventId);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
