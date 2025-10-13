<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;

class CrudFieldsConfiguredEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private readonly string $pageName,
        private iterable $fields,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getFields(): iterable
    {
        return $this->fields;
    }

    public function setFields(iterable $fields): void
    {
        $this->fields = $fields;
    }

    public function addField($field): void
    {
        if (is_array($this->fields)) {
            $this->fields[] = $field;
        } else {
            // Convert iterable to array if it's not already
            $this->fields = [...$this->fields, $field];
        }
    }
}
