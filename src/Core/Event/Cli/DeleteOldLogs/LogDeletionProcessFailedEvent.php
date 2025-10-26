<?php

namespace App\Core\Event\Cli\DeleteOldLogs;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class LogDeletionProcessFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $failureReason,
        private readonly ?int $daysAfter,
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

    public function getDaysAfter(): ?int
    {
        return $this->daysAfter;
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
