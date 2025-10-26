<?php

namespace App\Core\Event\Cli\DeleteInactiveServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class DeleteInactiveServersProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serversChecked,
        private readonly int $serversDeleted,
        private readonly int $serversSkipped,
        private readonly int $serversFailed,
        private readonly int $daysAfterExpiration,
        private readonly int $durationInSeconds,
        private readonly DateTimeImmutable $completedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getServersChecked(): int
    {
        return $this->serversChecked;
    }

    public function getServersDeleted(): int
    {
        return $this->serversDeleted;
    }

    public function getServersSkipped(): int
    {
        return $this->serversSkipped;
    }

    public function getServersFailed(): int
    {
        return $this->serversFailed;
    }

    public function getDaysAfterExpiration(): int
    {
        return $this->daysAfterExpiration;
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
