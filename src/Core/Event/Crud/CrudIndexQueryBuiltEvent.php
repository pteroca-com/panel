<?php

namespace App\Core\Event\Crud;

use App\Core\Contract\UserInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class CrudIndexQueryBuiltEvent extends AbstractCrudEvent
{
    public function __construct(
        string $entityFqcn,
        private QueryBuilder $queryBuilder,
        private readonly SearchDto $searchDto,
        private readonly EntityDto $entityDto,
        private readonly FieldCollection $fields,
        private readonly FilterCollection $filters,
        ?UserInterface $user = null,
        array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($entityFqcn, $user, $context, $eventId);
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getSearchDto(): SearchDto
    {
        return $this->searchDto;
    }

    public function getEntityDto(): EntityDto
    {
        return $this->entityDto;
    }

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    public function getFilters(): FilterCollection
    {
        return $this->filters;
    }
}
