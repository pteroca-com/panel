<?php

namespace App\Core\Contract\Tab;

use App\Core\DTO\ServerTabContext;

interface ServerTabInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getPriority(): int;

    public function isVisible(ServerTabContext $context): bool;

    public function isDefault(): bool;

    public function getTemplate(): string;

    public function getStylesheets(): array;

    public function getJavascripts(): array;

    public function requiresFullReload(): bool;
}
