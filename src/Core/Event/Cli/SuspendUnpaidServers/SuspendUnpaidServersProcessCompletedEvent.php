<?php

namespace App\Core\Event\Cli\SuspendUnpaidServers;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class SuspendUnpaidServersProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $serversChecked,
        private readonly int $serversSuspended,
        private readonly int $serversRenewed,
        private readonly int $serversFailedToProcess,
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

    public function getServersSuspended(): int
    {
        return $this->serversSuspended;
    }

    public function getServersRenewed(): int
    {
        return $this->serversRenewed;
    }

    public function getServersFailedToProcess(): int
    {
        return $this->serversFailedToProcess;
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
