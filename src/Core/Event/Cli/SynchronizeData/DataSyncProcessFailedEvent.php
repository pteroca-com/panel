<?php

namespace App\Core\Event\Cli\SynchronizeData;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class DataSyncProcessFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $failureReason,
        private readonly array $stats,
        private readonly DateTimeImmutable $failedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getFailedAt(): DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
