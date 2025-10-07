<?php

namespace App\Core\Event\View;

use App\Core\Contract\UserInterface;
use App\Core\Event\AbstractDomainEvent;

class ViewDataEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $viewName,
        private array $viewData,
        private readonly ?UserInterface $user = null,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }

    public function getViewData(): array
    {
        return $this->viewData;
    }

    public function setViewData(string $key, mixed $value): void
    {
        $this->viewData[$key] = $value;
    }

    public function addToViewData(array $data): void
    {
        $this->viewData = array_merge($this->viewData, $data);
    }

    public function hasViewData(string $key): bool
    {
        return isset($this->viewData[$key]);
    }

    public function getViewValue(string $key): mixed
    {
        return $this->viewData[$key] ?? null;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
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
