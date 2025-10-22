<?php

namespace App\Core\Event\Server\User;

use App\Core\Event\AbstractDomainEvent;

class ServerSubuserPermissionsUpdatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly string $subuserEmail,
        private readonly string $subuserUuid,
        private readonly array $oldPermissions,
        private readonly array $newPermissions,
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

    public function getSubuserEmail(): string
    {
        return $this->subuserEmail;
    }

    public function getSubuserUuid(): string
    {
        return $this->subuserUuid;
    }

    public function getOldPermissions(): array
    {
        return $this->oldPermissions;
    }

    public function getNewPermissions(): array
    {
        return $this->newPermissions;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
