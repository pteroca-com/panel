<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use App\Core\Event\AbstractDomainEvent;

abstract class AbstractCrudEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly string $entityFqcn,
        private readonly ?UserInterface $user = null,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getId();
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
