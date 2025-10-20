<?php

namespace App\Core\Event\Page;

use App\Core\Event\AbstractDomainEvent;

class PageDataLoadedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ?int $userId,
        private readonly string $pageType,
        private readonly bool $hasContent,
        private readonly int $contentLength,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getPageType(): string
    {
        return $this->pageType;
    }

    public function hasContent(): bool
    {
        return $this->hasContent;
    }

    public function getContentLength(): int
    {
        return $this->contentLength;
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
