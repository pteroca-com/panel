<?php

namespace App\Core\Controller\Panel;

use App\Core\Enum\LogActionEnum;
use App\Core\Service\Crud\PanelCrudService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

abstract class AbstractPanelController extends AbstractCrudController
{
    private array $crudTemplateContext = [];

    public function __construct(
        private readonly PanelCrudService $panelCrudService,
    ) {
    }

    public function appendCrudTemplateContext(string $templateContext): void
    {
        $this->crudTemplateContext[] = $templateContext;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->overrideTemplates($this->panelCrudService->getTemplatesToOverride($this->crudTemplateContext));

        return parent::configureCrud($crud);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);
        $this->logEntityAction(LogActionEnum::ENTITY_ADD, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);
        $this->logEntityAction(LogActionEnum::ENTITY_EDIT, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::deleteEntity($entityManager, $entityInstance);
        $this->logEntityAction(LogActionEnum::ENTITY_DELETE, $entityInstance);
    }

    private function logEntityAction(LogActionEnum $action, $entityInstance): void
    {
        $this->panelCrudService
            ->logEntityAction($action, $entityInstance, $this->getUser(), $this->getEntityFqcn());
    }
}
