<?php

namespace App\Core\Event\Server\Configuration;

use App\Core\Event\AbstractDomainEvent;

class ServerReinstallInitiatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $serverId,
        private readonly string $serverPterodactylIdentifier,
        private readonly ?int $selectedEgg,
        private readonly int $currentEgg,
        private readonly bool $eggChanged,
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

    public function getSelectedEgg(): ?int
    {
        return $this->selectedEgg;
    }

    public function getCurrentEgg(): int
    {
        return $this->currentEgg;
    }

    public function isEggChanged(): bool
    {
        return $this->eggChanged;
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
