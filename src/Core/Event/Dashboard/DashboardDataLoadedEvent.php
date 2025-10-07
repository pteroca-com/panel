<?php

namespace App\Core\Event\Dashboard;

use App\Core\Event\AbstractDomainEvent;

class DashboardDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serversCount,
        private readonly int $recentLogsCount,
        private readonly bool $motdEnabled,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getServersCount(): int
    {
        return $this->serversCount;
    }

    public function getRecentLogsCount(): int
    {
        return $this->recentLogsCount;
    }

    public function isMotdEnabled(): bool
    {
        return $this->motdEnabled;
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
}
