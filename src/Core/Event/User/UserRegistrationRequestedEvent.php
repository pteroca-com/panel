<?php

namespace App\Core\Event\User;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserRegistrationRequestedEvent extends Event
{
    use StoppableEventTrait;

    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    private string $schemaVersion = 'v1';

    public function __construct(
        private readonly string $email,
        private readonly array $context = [] // ip, userAgent, locale, source, referralCode, consents
    ) {
        $this->eventId = \Symfony\Component\Uid\Uuid::v4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getIp(): ?string
    {
        return $this->context['ip'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->context['userAgent'] ?? null;
    }

    public function getLocale(): ?string
    {
        return $this->context['locale'] ?? null;
    }

    public function getSource(): ?string
    {
        return $this->context['source'] ?? null;
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
