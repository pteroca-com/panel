<?php

namespace App\Core\Controller\Panel;

use App\Core\Entity\Setting;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Service\Logs\LogService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractPanelController extends AbstractCrudController
{
    private Serializer $serializer;

    public function __construct(
        private readonly LogService $logService,
    ) {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    private function logEntityAction(LogActionEnum $action, $entityInstance): void
    {
        if (is_a($entityInstance, Setting::class)
            && $entityInstance->getType() === SettingTypeEnum::SECRET->value) {
            $entityInstance->setValue('********');
        }
        $this->logService->logAction(
            $this->getUser(),
            $action,
            [
                'entityName' => $this->getEntityFqcn(),
                'entity' => $this->serializer->normalize($entityInstance, null, ['groups' => 'log'])
            ],
        );
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
}
