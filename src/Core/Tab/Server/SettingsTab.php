<?php

namespace App\Core\Tab\Server;

use App\Core\Contract\Tab\ServerTabInterface;
use App\Core\DTO\ServerTabContext;

class SettingsTab implements ServerTabInterface
{
    public function getId(): string
    {
        return 'settings';
    }

    public function getLabel(): string
    {
        return 'pteroca.server.settings';
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function isVisible(ServerTabContext $context): bool
    {
        return true; // Always visible
    }

    public function isDefault(): bool
    {
        return false;
    }

    public function getTemplate(): string
    {
        return 'panel/server/tabs/settings/settings.html.twig';
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
