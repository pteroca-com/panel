<?php

namespace App\Core\Event\Form;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class FormSubmitEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly string $formType,
        private array $formData,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(string $key, mixed $value): void
    {
        $this->formData[$key] = $value;
    }

    public function getFormValue(string $key): mixed
    {
        return $this->formData[$key] ?? null;
    }

    public function hasFormValue(string $key): bool
    {
        return isset($this->formData[$key]);
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
