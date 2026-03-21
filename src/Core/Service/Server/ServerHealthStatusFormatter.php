<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Enum\ServerHealthStatusEnum;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerHealthStatusFormatter
{
    public function __construct(
        private readonly ServerHealthStatusService $serverHealthStatusService,
    ) {}

    public function getHealthBadgeHtml(Server $server, TranslatorInterface $translator): string
    {
        $status = $this->serverHealthStatusService->checkServerHealth($server);

        return match ($status) {
            ServerHealthStatusEnum::HEALTHY => sprintf(
                '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.healthy')
            ),
            ServerHealthStatusEnum::SUSPENDED => sprintf(
                '<span class="badge bg-danger"><i class="fas fa-ban me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.suspended')
            ),
            ServerHealthStatusEnum::EXPIRED => sprintf(
                '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.expired')
            ),
            ServerHealthStatusEnum::EXPIRED_NOT_SUSPENDED => sprintf(
                '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.expired_not_suspended')
            ),
            ServerHealthStatusEnum::EXPIRING_CRITICAL => sprintf(
                '<span class="badge bg-warning"><i class="fas fa-exclamation-circle me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.expiring_critical')
            ),
            ServerHealthStatusEnum::EXPIRING_SOON => sprintf(
                '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.expiring_soon')
            ),
            ServerHealthStatusEnum::DELETED => sprintf(
                '<span class="badge bg-secondary"><i class="fas fa-trash me-1"></i>%s</span>',
                $translator->trans('pteroca.crud.server.health_status.deleted')
            ),
        };
    }
}
