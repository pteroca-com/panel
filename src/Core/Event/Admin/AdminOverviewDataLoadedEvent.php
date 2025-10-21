<?php

namespace App\Core\Event\Admin;

use App\Core\Event\AbstractDomainEvent;

class AdminOverviewDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $activeServersCount,
        private readonly int $usersRegisteredLastMonthCount,
        private readonly int $paymentsCreatedLastMonthCount,
        private readonly bool $pterodactylOnline,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getActiveServersCount(): int
    {
        return $this->activeServersCount;
    }

    public function getUsersRegisteredLastMonthCount(): int
    {
        return $this->usersRegisteredLastMonthCount;
    }

    public function getPaymentsCreatedLastMonthCount(): int
    {
        return $this->paymentsCreatedLastMonthCount;
    }

    public function isPterodactylOnline(): bool
    {
        return $this->pterodactylOnline;
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
