<?php

namespace App\Core\Service\Widget;

use App\Core\Contract\Widget\DashboardWidgetInterface;
use App\Core\Enum\WidgetPosition;

class DashboardWidgetRegistry
{
    private array $widgets = [];

    public function registerWidget(DashboardWidgetInterface $widget): void
    {
        $this->widgets[$widget->getName()] = $widget;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function getWidgetsByPosition(WidgetPosition $position): array
    {
        $widgets = array_filter(
            $this->widgets,
            fn(DashboardWidgetInterface $widget) => $widget->getPosition() === $position
        );

        usort($widgets, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $widgets;
    }

    public function hasWidget(string $name): bool
    {
        return isset($this->widgets[$name]);
    }

    public function getWidget(string $name): ?DashboardWidgetInterface
    {
        return $this->widgets[$name] ?? null;
    }

    public function removeWidget(string $name): void
    {
        unset($this->widgets[$name]);
    }

    public function count(): int
    {
        return count($this->widgets);
    }
}
