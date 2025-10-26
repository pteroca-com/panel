<?php

namespace App\Core\Event\Cli\DeleteInactiveServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class DeleteInactiveServersProcessStartedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DateTimeImmutable $startedAt,
        private readonly int $daysAfterExpiration,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getDaysAfterExpiration(): int
    {
        return $this->daysAfterExpiration;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
