<?php

namespace App\Core\Service\Crud;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Setting;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Service\Logs\LogService;
use Symfony\Component\Serializer\SerializerInterface;

class PanelCrudService
{
    public function __construct(
        private readonly CrudTemplateService $crudTemplateService,
        private readonly LogService $logService,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    public function logEntityAction(LogActionEnum $action, $entityInstance, UserInterface $user, string $entityName): void
    {
        if (is_a($entityInstance, Setting::class)
            && $entityInstance->getType() === SettingTypeEnum::SECRET->value) {
            $entityInstance->setValue('********');
        }
        $this->logService->logAction(
            $user,
            $action,
            [
                'entityName' => $entityName,
                'entity' => $this->serializer->normalize($entityInstance, null, ['groups' => 'log'])
            ],
        );
    }

    public function getTemplatesToOverride(array $templateContext): array
    {
        return $this->crudTemplateService->getTemplatesToOverride($templateContext);
    }
}
