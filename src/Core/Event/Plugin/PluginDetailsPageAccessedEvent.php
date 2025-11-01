<?php

namespace App\Core\Event\Plugin;

use App\Core\Event\AbstractDomainEvent;

class PluginDetailsPageAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?int $userId,
        private readonly string $pluginName,
        private readonly ?int $pluginId,
        private readonly ?string $pluginState,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function getPluginId(): ?int
    {
        return $this->pluginId;
    }

    public function getPluginState(): ?string
    {
        return $this->pluginState;
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
