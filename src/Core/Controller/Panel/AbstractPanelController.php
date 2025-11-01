<?php

namespace App\Core\Controller\Panel;

use App\Core\Enum\LogActionEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Crud\CrudActionsConfiguredEvent;
use App\Core\Event\Crud\CrudConfiguredEvent;
use App\Core\Event\Crud\CrudEntityDeletedEvent;
use App\Core\Event\Crud\CrudEntityDeletingEvent;
use App\Core\Event\Crud\CrudEntityPersistedEvent;
use App\Core\Event\Crud\CrudEntityPersistingEvent;
use App\Core\Event\Crud\CrudEntityUpdatedEvent;
use App\Core\Event\Crud\CrudEntityUpdatingEvent;
use App\Core\Event\Crud\CrudFieldsConfiguredEvent;
use App\Core\Event\Crud\CrudFiltersConfiguredEvent;
use App\Core\Event\Crud\CrudIndexQueryBuiltEvent;
use App\Core\Service\Crud\PanelCrudService;
use App\Core\Trait\EventContextTrait;
use App\Core\Trait\GetUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPanelController extends AbstractCrudController
{
    use GetuserTrait;
    use EventContextTrait;

    private array $crudTemplateContext = [];

    public function __construct(
        private readonly PanelCrudService $panelCrudService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function appendCrudTemplateContext(string $templateContext): void
    {
        $this->crudTemplateContext[] = $templateContext;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->overrideTemplates($this->panelCrudService->getTemplatesToOverride($this->crudTemplateContext));

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $event = new CrudConfiguredEvent(
            static::getEntityFqcn(),
            $crud,
            $this->getUser(),
            $context
        );

        $event = $this->dispatchEvent($event);
        $crud = $event->getCrud();

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $event = new CrudActionsConfiguredEvent(
            static::getEntityFqcn(),
            $actions,
            $this->getUser(),
            $context
        );

        $event = $this->dispatchEvent($event);
        $actions = $event->getActions();

        return parent::configureActions($actions);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $event = new CrudFiltersConfiguredEvent(
            static::getEntityFqcn(),
            $filters,
            $this->getUser(),
            $context
        );

        $event = $this->dispatchEvent($event);
        $filters = $event->getFilters();

        return parent::configureFilters($filters);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [];

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $event = new CrudFieldsConfiguredEvent(
            static::getEntityFqcn(),
            $pageName,
            $fields,
            $this->getUser(),
            $context
        );

        $event = $this->dispatchEvent($event);

        return $event->getFields();
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $appliedFilters = $searchDto->getAppliedFilters();

        if ($entityDto->hasProperty('deletedAt')) {
            if (!array_key_exists('deletedAt', $appliedFilters)) {
                $qb->andWhere('entity.deletedAt IS NULL');
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $event = new CrudIndexQueryBuiltEvent(
            static::getEntityFqcn(),
            $qb,
            $searchDto,
            $entityDto,
            $fields,
            $filters,
            $this->getUser(),
            $context
        );

        $event = $this->dispatchEvent($event);
        $qb = $event->getQueryBuilder();

        return $qb;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $persistingEvent = new CrudEntityPersistingEvent(
            static::getEntityFqcn(),
            $entityInstance,
            $this->getUser(),
            $context
        );

        $persistingEvent = $this->dispatchEvent($persistingEvent);

        if (!$persistingEvent->isPropagationStopped()) {
            parent::persistEntity($entityManager, $entityInstance);

            $persistedEvent = new CrudEntityPersistedEvent(
                static::getEntityFqcn(),
                $entityInstance,
                $this->getUser(),
                $context
            );

            $this->dispatchEvent($persistedEvent);
            $this->logEntityAction(LogActionEnum::ENTITY_ADD, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $updatingEvent = new CrudEntityUpdatingEvent(
            static::getEntityFqcn(),
            $entityInstance,
            $this->getUser(),
            $context
        );

        $updatingEvent = $this->dispatchEvent($updatingEvent);

        if (!$updatingEvent->isPropagationStopped()) {
            parent::updateEntity($entityManager, $entityInstance);

            $updatedEvent = new CrudEntityUpdatedEvent(
                static::getEntityFqcn(),
                $entityInstance,
                $this->getUser(),
                $context
            );

            $this->dispatchEvent($updatedEvent);
            $this->logEntityAction(LogActionEnum::ENTITY_EDIT, $entityInstance);
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->buildMinimalEventContext($request) : [];

        $deletingEvent = new CrudEntityDeletingEvent(
            static::getEntityFqcn(),
            $entityInstance,
            $this->getUser(),
            $context
        );

        $deletingEvent = $this->dispatchEvent($deletingEvent);

        if (!$deletingEvent->isPropagationStopped()) {
            parent::deleteEntity($entityManager, $entityInstance);

            $deletedEvent = new CrudEntityDeletedEvent(
                static::getEntityFqcn(),
                $entityInstance,
                $this->getUser(),
                $context
            );

            $this->dispatchEvent($deletedEvent);
            $this->logEntityAction(LogActionEnum::ENTITY_DELETE, $entityInstance);
        }
    }

    private function logEntityAction(LogActionEnum $action, $entityInstance): void
    {
        $this->panelCrudService
            ->logEntityAction($action, $entityInstance, $this->getUser(), $this->getEntityFqcn());
    }

    protected function renderWithEvent(
        ViewNameEnum $viewName,
        string $template,
        array $viewData,
        Request $request
    ): Response
    {
        $viewEvent = $this->prepareViewDataEvent($viewName, $viewData, $request);

        return $this->render($template, $viewEvent->getViewData());
    }
}
