<?php

namespace App\Core\Event\Server\Configuration;

use App\Core\Event\AbstractDomainEvent;

class ServerDetailsUpdatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $serverName,
        private readonly ?string $serverDescription,
        private readonly string $oldServerName,
        private readonly ?string $oldServerDescription,
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

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getServerDescription(): ?string
    {
        return $this->serverDescription;
    }

    public function getOldServerName(): string
    {
        return $this->oldServerName;
    }

    public function getOldServerDescription(): ?string
    {
        return $this->oldServerDescription;
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
