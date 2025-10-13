<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

class CrudFiltersConfiguredEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private Filters $filters,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getFilters(): Filters
    {
        return $this->filters;
    }

    public function setFilters(Filters $filters): void
    {
        $this->filters = $filters;
    }
}
