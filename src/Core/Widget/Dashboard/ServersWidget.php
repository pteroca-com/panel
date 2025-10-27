<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Contract\Widget\DashboardWidgetInterface;
use App\Core\Enum\WidgetPosition;
use App\Core\Service\Server\ServerService;

class ServersWidget implements DashboardWidgetInterface
{
    public function __construct(
        private readonly ServerService $serverService
    ) {}

    public function getName(): string
    {
        return 'servers';
    }

    public function getDisplayName(): string
    {
        return 'My Servers';
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::LEFT;
    }

    public function getPriority(): int
    {
        return 100; // High priority - show first in left column
    }

    public function getTemplate(): string
    {
        return 'panel/dashboard/components/servers.html.twig';
    }

    public function getData(UserInterface $user): array
    {
        return [
            'servers' => $this->serverService->getServersWithAccess($user),
        ];
    }

    public function isVisible(UserInterface $user): bool
    {
        return true; // Always visible for authenticated users
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within LEFT position
    }
}
