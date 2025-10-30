<?php

namespace App\Core\Service\Widget;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * Universal registry for widgets across different contexts.
 *
 * Manages widget registration, retrieval, and ordering with context awareness.
 * Widgets are automatically sorted by priority within each position and context.
 *
 * Supports multiple contexts (dashboard, admin overview, server detail, etc.)
 * allowing widgets to be reused across different pages.
 */
class WidgetRegistry
{
    /** @var array<string, WidgetInterface> */
    private array $widgets = [];

    /**
     * Register a widget in the registry.
     * If widget with same name exists, it will be replaced.
     *
     * @param WidgetInterface $widget Widget to register
     * @return void
     */
    public function registerWidget(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getName()] = $widget;
    }

    /**
     * Get all registered widgets (unsorted, all contexts).
     *
     * @return array<string, WidgetInterface>
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Get widgets that support specific context.
     * Filters widgets by their getSupportedContexts() return value.
     *
     * @param WidgetContext $context Context to filter by
     * @return array<WidgetInterface> Widgets supporting the context
     */
    public function getWidgetsForContext(WidgetContext $context): array
    {
        return array_filter(
            $this->widgets,
            fn(WidgetInterface $widget) => in_array($context, $widget->getSupportedContexts(), true)
        );
    }

    /**
     * Get widgets for specific position and context, sorted by priority (DESC).
     *
     * This is the primary method for rendering widgets in templates.
     *
     * @param WidgetPosition $position Position to filter by
     * @param WidgetContext $context Context to filter by
     * @return array<WidgetInterface> Sorted widgets (highest priority first)
     */
    public function getWidgetsByPosition(WidgetPosition $position, WidgetContext $context): array
    {
        $widgets = array_filter(
            $this->getWidgetsForContext($context),
            fn(WidgetInterface $widget) => $widget->getPosition() === $position
        );

        // Sort by priority DESC (higher priority first)
        usort($widgets, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $widgets;
    }

    /**
     * Check if widget with given name is registered.
     *
     * @param string $name Widget name
     * @return bool True if registered
     */
    public function hasWidget(string $name): bool
    {
        return isset($this->widgets[$name]);
    }

    /**
     * Get widget by name.
     *
     * @param string $name Widget name
     * @return WidgetInterface|null Widget or null if not found
     */
    public function getWidget(string $name): ?WidgetInterface
    {
        return $this->widgets[$name] ?? null;
    }

    /**
     * Remove widget from registry.
     *
     * @param string $name Widget name
     * @return void
     */
    public function removeWidget(string $name): void
    {
        unset($this->widgets[$name]);
    }

    /**
     * Get count of registered widgets.
     *
     * @param WidgetContext|null $context Optional: count only widgets supporting this context
     * @return int Number of widgets
     */
    public function count(?WidgetContext $context = null): int
    {
        if ($context === null) {
            return count($this->widgets);
        }

        return count($this->getWidgetsForContext($context));
    }
}
