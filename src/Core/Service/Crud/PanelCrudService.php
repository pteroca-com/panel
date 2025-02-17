<?php

namespace App\Core\Service\Crud;

use App\Core\Entity\Setting;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Service\Logs\LogService;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class PanelCrudService
{
    private Serializer $serializer;

    public function __construct(
        private readonly CrudTemplateService $crudTemplateService,
        private readonly LogService $logService,
    ) {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function logEntityAction(LogActionEnum $action, $entityInstance, User $user, string $entityName): void
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
