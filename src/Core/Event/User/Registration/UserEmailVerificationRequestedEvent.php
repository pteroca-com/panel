<?php

namespace App\Core\Event\User\Registration;

use App\Core\Event\AbstractDomainEvent;


class UserEmailVerificationRequestedEvent extends AbstractDomainEvent
{

    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly string $verificationToken,
        private readonly array $context = [],
        private readonly ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getVerificationToken(): string
    {
        return $this->verificationToken;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
