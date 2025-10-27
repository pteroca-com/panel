<?php

namespace App\Core\Event\Cli\SynchronizeData;

use App\Core\Event\AbstractDomainEvent;
use DateTimeImmutable;

class DataSyncProcessCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $usersWithoutKeys,
        private readonly int $keysCreated,
        private readonly int $keysSkipped,
        private readonly int $keysFailed,
        private readonly int $durationInSeconds,
        private readonly DateTimeImmutable $completedAt,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUsersWithoutKeys(): int
    {
        return $this->usersWithoutKeys;
    }

    public function getKeysCreated(): int
    {
        return $this->keysCreated;
    }

    public function getKeysSkipped(): int
    {
        return $this->keysSkipped;
    }

    public function getKeysFailed(): int
    {
        return $this->keysFailed;
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
