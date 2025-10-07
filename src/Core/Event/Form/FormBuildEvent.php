<?php

namespace App\Core\Event\Form;

use App\Core\Event\AbstractDomainEvent;
use Symfony\Component\Form\FormBuilderInterface;

class FormBuildEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly FormBuilderInterface $form,
        private readonly string $formType,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getForm(): FormBuilderInterface
    {
        return $this->form;
    }

    public function getFormType(): string
    {
        return $this->formType;
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
