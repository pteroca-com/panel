<?php

namespace App\Core\Event\Server;

use App\Core\Event\AbstractDomainEvent;

class ServerManagementDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly bool $isInstalling,
        private readonly bool $isSuspended,
        private readonly bool $hasPermissions,
        private readonly array $loadedDataSections,
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

    public function isInstalling(): bool
    {
        return $this->isInstalling;
    }

    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function hasPermissions(): bool
    {
        return $this->hasPermissions;
    }

    public function getLoadedDataSections(): array
    {
        return $this->loadedDataSections;
    }

    public function hasSectionLoaded(string $section): bool
    {
        return in_array($section, $this->loadedDataSections, true);
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
