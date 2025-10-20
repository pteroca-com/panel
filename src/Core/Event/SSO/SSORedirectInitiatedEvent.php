<?php

namespace App\Core\Event\SSO;

use App\Core\Event\AbstractDomainEvent;

class SSORedirectInitiatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $pterodactylUserId,
        private readonly string $redirectPath,
        private readonly string $targetUrl,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPterodactylUserId(): int
    {
        return $this->pterodactylUserId;
    }

    public function getRedirectPath(): string
    {
        return $this->redirectPath;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
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
