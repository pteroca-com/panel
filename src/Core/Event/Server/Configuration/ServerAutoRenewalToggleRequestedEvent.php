<?php

namespace App\Core\Event\Server\Configuration;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class ServerAutoRenewalToggleRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly bool $newValue,
        private readonly bool $currentValue,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getServerPterodactylIdentifier(): string
    {
        return $this->serverPterodactylIdentifier;
    }

    public function getNewValue(): bool
    {
        return $this->newValue;
    }

    public function getCurrentValue(): bool
    {
        return $this->currentValue;
    }

    public function isEnabling(): bool
    {
        return $this->newValue === true;
    }

    public function isDisabling(): bool
    {
        return $this->newValue === false;
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
