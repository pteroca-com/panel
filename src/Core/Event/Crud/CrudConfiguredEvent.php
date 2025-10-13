<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class CrudConfiguredEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private Crud $crud,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getCrud(): Crud
    {
        return $this->crud;
    }

    public function setCrud(Crud $crud): void
    {
        $this->crud = $crud;
    }
}
