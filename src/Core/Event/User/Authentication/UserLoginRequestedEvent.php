<?php

namespace App\Core\Event\User\Authentication;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class UserLoginRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
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

    public function getReferer(): ?string
    {
        return $this->context['referer'] ?? null;
    }
}
