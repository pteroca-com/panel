<?php

namespace App\Core\Event\Server\Backup;

use App\Core\Event\AbstractDomainEvent;

class ServerBackupCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $backupId,
        private readonly string $backupName,
        private readonly ?string $ignoredFiles,
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

    public function getBackupId(): string
    {
        return $this->backupId;
    }

    public function getBackupName(): string
    {
        return $this->backupName;
    }

    public function getIgnoredFiles(): ?string
    {
        return $this->ignoredFiles;
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
