<?php

namespace App\Core\Event\SSO;

use App\Core\Event\AbstractDomainEvent;
use DateTimeInterface;

class SSOTokenGeneratedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $pterodactylUserId,
        private readonly string $tokenHash,
        private readonly DateTimeInterface $expiresAt,
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

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expiresAt;
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
