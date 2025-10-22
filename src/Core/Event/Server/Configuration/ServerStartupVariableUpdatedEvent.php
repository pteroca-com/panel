<?php

namespace App\Core\Event\Server\Configuration;

use App\Core\Event\AbstractDomainEvent;

class ServerStartupVariableUpdatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $variableKey,
        private readonly string $variableValue,
        private readonly string $oldValue,
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

    public function getVariableKey(): string
    {
        return $this->variableKey;
    }

    public function getVariableValue(): string
    {
        return $this->variableValue;
    }

    public function getOldValue(): string
    {
        return $this->oldValue;
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
