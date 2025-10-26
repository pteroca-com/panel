<?php

namespace App\Core\Event\Cli\DeleteOldLogs;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class LogDeletionProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $daysAfter,
        private readonly DateTimeImmutable $cutoffDate,
        private readonly int $deletedLogs,
        private readonly int $deletedServerLogs,
        private readonly int $deletedEmailLogs,
        private readonly int $totalDeleted,
        private readonly int $durationInSeconds,
        private readonly DateTimeImmutable $completedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getDaysAfter(): int
    {
        return $this->daysAfter;
    }

    public function getCutoffDate(): DateTimeImmutable
    {
        return $this->cutoffDate;
    }

    public function getDeletedLogs(): int
    {
        return $this->deletedLogs;
    }

    public function getDeletedServerLogs(): int
    {
        return $this->deletedServerLogs;
    }

    public function getDeletedEmailLogs(): int
    {
        return $this->deletedEmailLogs;
    }

    public function getTotalDeleted(): int
    {
        return $this->totalDeleted;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getCompletedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
