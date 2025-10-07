<?php

namespace App\Core\Event\User\Registration;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Contract\UserInterface;
use App\Core\Event\StoppableEventTrait;

class UserAboutToBeCreatedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly UserInterface $user,
        private readonly ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->user->getEmail();
    }

    public function getRoles(): array
    {
        return $this->user->getRoles();
    }
}
