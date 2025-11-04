<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use App\Core\Service\Server\ServerService;
use InvalidArgumentException;

readonly class ServersWidget implements WidgetInterface
{
    public function __construct(
        private ServerService $serverService
    ) {}

    public function getName(): string
    {
        return 'servers';
    }

    public function getDisplayName(): string
    {
        return 'My Servers';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::DASHBOARD];
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

    public function getData(WidgetContext $context, array $contextData): array
    {
        /** @var UserInterface $user */
        $user = $contextData['user'] ?? throw new InvalidArgumentException('User required in context data');

        return [
            'servers' => $this->serverService->getServersWithAccess($user),
        ];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        return $context === WidgetContext::DASHBOARD;
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within LEFT position
    }
}
