<?php

namespace App\Core\Event\Cli\DeleteOldLogs;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class LogDeletionProcessStartedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DateTimeImmutable $startedAt,
        private readonly int $daysAfter,
        private readonly DateTimeImmutable $cutoffDate,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getDaysAfter(): int
    {
        return $this->daysAfter;
    }

    public function getCutoffDate(): DateTimeImmutable
    {
        return $this->cutoffDate;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
