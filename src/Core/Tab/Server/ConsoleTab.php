<?php

namespace App\Core\Tab\Server;

use App\Core\Contract\Tab\ServerTabInterface;
use App\Core\DTO\ServerTabContext;

class ConsoleTab implements ServerTabInterface
{
    public function getId(): string
    {
        return 'console';
    }

    public function getLabel(): string
    {
        return 'pteroca.server.console';
    }

    public function getPriority(): int
    {
        return 100; // Always first
    }

    public function isVisible(ServerTabContext $context): bool
    {
        return true; // Always visible
    }

    public function isDefault(): bool
    {
        return true; // Default active tab
    }

    public function getTemplate(): string
    {
        return 'panel/server/tabs/console/console.html.twig';
    }

    public function getStylesheets(): array
    {
        return ['css/xterm.min.css'];
    }

    public function getJavascripts(): array
    {
        return ['js/libraries/xterm.min.js'];
    }

    public function requiresFullReload(): bool
    {
        return false;
    }
}
