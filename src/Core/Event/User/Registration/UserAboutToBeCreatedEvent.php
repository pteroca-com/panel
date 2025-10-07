<?php

namespace App\Core\Event\User\Registration;

use App\Core\Contract\UserInterface;
use App\Core\Event\StoppableEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserAboutToBeCreatedEvent extends Event
{
    use StoppableEventTrait;

    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';

    public function __construct(
        private readonly UserInterface $user
    ) {
        $this->eventId = \Symfony\Component\Uid\Uuid::v4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
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

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }
}
