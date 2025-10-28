<?php

namespace App\Core\Tab\Server;

use App\Core\Contract\Tab\ServerTabInterface;
use App\Core\DTO\ServerTabContext;

class ActivityTab implements ServerTabInterface
{
    public function getId(): string
    {
        return 'activity';
    }

    public function getLabel(): string
    {
        return 'pteroca.server.activity';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function isVisible(ServerTabContext $context): bool
    {
        return $context->hasPermission('activity.read');
    }

    public function isDefault(): bool
    {
        return false;
    }

    public function getTemplate(): string
    {
        return 'panel/server/tabs/activity/activity.html.twig';
    }

    public function getStylesheets(): array
    {
        return [];
    }

    public function getJavascripts(): array
    {
        return [];
    }

    public function requiresFullReload(): bool
    {
        return false;
    }
}
