<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class CrudActionsConfiguredEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private Actions $actions,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getActions(): Actions
    {
        return $this->actions;
    }

    public function setActions(Actions $actions): void
    {
        $this->actions = $actions;
    }
}
