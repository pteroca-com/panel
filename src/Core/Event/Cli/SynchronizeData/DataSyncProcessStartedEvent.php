<?php

namespace App\Core\Event\Cli\SynchronizeData;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class DataSyncProcessStartedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DateTimeImmutable $startedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
