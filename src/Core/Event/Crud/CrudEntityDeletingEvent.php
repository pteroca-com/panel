<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use App\Core\Event\StoppableEventTrait;

class CrudEntityDeletingEvent extends AbstractCrudEvent
{
    use StoppableEventTrait;

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
