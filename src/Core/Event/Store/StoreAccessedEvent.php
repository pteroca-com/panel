<?php

namespace App\Core\Event\Store;

use App\Core\Event\AbstractDomainEvent;

class StoreAccessedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?int $userId,
        private readonly array $context = [],
    ) {
        parent::__construct();
    }

    public function getUserId(): ?int
    {
        return $this->userId;
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
