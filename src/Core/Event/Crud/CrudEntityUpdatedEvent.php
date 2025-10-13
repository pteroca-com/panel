<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;

class CrudEntityUpdatedEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private readonly object $entityInstance,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getEntityInstance(): object
    {
        return $this->entityInstance;
    }
}
