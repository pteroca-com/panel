<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Contract\Widget\DashboardWidgetInterface;
use App\Core\Enum\WidgetPosition;
use App\Core\Service\Logs\LogService;

class ActivityWidget implements DashboardWidgetInterface
{
    public function __construct(
        private readonly LogService $logService
    ) {}

    public function getName(): string
    {
        return 'activity';
    }

    public function getDisplayName(): string
    {
        return 'Recent Activity';
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::RIGHT;
    }

    public function getPriority(): int
    {
        return 80; // Below balance (100) and MOTD (90), show third
    }

    public function getTemplate(): string
    {
        return 'panel/dashboard/components/activity.html.twig';
    }

    public function getData(UserInterface $user): array
    {
        return [
            'logs' => $this->logService->getLogsByUser($user, 5),
        ];
    }

    public function isVisible(UserInterface $user): bool
    {
        return true; // Always visible for authenticated users
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within RIGHT position
    }
}
