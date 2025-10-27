<?php

namespace App\Core\Contract\Widget;

use App\Core\Contract\UserInterface;
use App\Core\Enum\WidgetPosition;

interface DashboardWidgetInterface
{
    public function getName(): string;

    public function getDisplayName(): string;

    public function getPosition(): WidgetPosition;

    public function getPriority(): int;

    public function getTemplate(): string;

    public function getData(UserInterface $user): array;

    public function isVisible(UserInterface $user): bool;

    public function getColumnSize(): int;
}
