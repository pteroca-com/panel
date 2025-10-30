<?php

namespace App\Core\Contract\Widget;

use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;

/**
 * Universal interface for widgets that can be registered by core or plugins.
 *
 * Widgets provide modular, extensible components that can be displayed in
 * different contexts (dashboard, admin overview, server detail, etc.) with:
 * - Context awareness (supports multiple rendering contexts)
 * - Flexible positioning (left, right, top, bottom, full-width)
 * - Priority-based ordering
 * - Conditional visibility
 * - Custom templates and data
 *
 * @example Single-context widget (dashboard only)
 * class BalanceWidget implements WidgetInterface
 * {
 *     public function getName(): string { return 'balance'; }
 *     public function getSupportedContexts(): array { return [WidgetContext::DASHBOARD]; }
 *     public function getPosition(): WidgetPosition { return WidgetPosition::RIGHT; }
 *     public function getPriority(): int { return 100; }
 *     public function getTemplate(): string { return 'panel/dashboard/components/balance.html.twig'; }
 *     public function getData(WidgetContext $context, array $contextData): array {
 *         $user = $contextData['user'];
 *         return ['balance' => $user->getBalance()];
 *     }
 *     public function isVisible(WidgetContext $context, array $contextData): bool {
 *         return $context === WidgetContext::DASHBOARD;
 *     }
 * }
 *
 * @example Multi-context widget (dashboard + admin overview)
 * class AnalyticsWidget implements WidgetInterface
 * {
 *     public function getSupportedContexts(): array {
 *         return [WidgetContext::DASHBOARD, WidgetContext::ADMIN_OVERVIEW];
 *     }
 *     public function getData(WidgetContext $context, array $contextData): array {
 *         return match($context) {
 *             WidgetContext::DASHBOARD => ['userStats' => $this->getUserStats($contextData['user'])],
 *             WidgetContext::ADMIN_OVERVIEW => ['systemStats' => $this->getSystemStats()],
 *             default => [],
 *         };
 *     }
 * }
 */
interface WidgetInterface
{
    /**
     * Unique widget identifier (lowercase, no spaces).
     * Used for registration and deduplication.
     *
     * @return string e.g., 'balance', 'server_list', 'custom_stats'
     */
    public function getName(): string;

    /**
     * Human-readable display name.
     *
     * @return string e.g., 'Account Balance', 'Server Statistics'
     */
    public function getDisplayName(): string;

    /**
     * Contexts where this widget can be displayed.
     * Widget will only be rendered in these contexts.
     *
     * @return array<WidgetContext> e.g., [WidgetContext::DASHBOARD, WidgetContext::ADMIN_OVERVIEW]
     */
    public function getSupportedContexts(): array;

    /**
     * Widget position in the layout.
     *
     * @return WidgetPosition LEFT, RIGHT, TOP, BOTTOM, or FULL_WIDTH
     */
    public function getPosition(): WidgetPosition;

    /**
     * Priority for ordering (higher = displayed first/higher).
     * Default widgets use 50-100 range.
     *
     * @return int e.g., 100 (high priority), 50 (normal), 10 (low)
     */
    public function getPriority(): int;

    /**
     * Twig template path for rendering.
     * Can use namespaced paths (@PluginName/...).
     *
     * @return string e.g., 'panel/dashboard/components/balance.html.twig'
     */
    public function getTemplate(): string;

    /**
     * Data to pass to the template.
     * Should return associative array with template variables.
     *
     * Context data structure varies by context:
     * - DASHBOARD: ['user' => UserInterface, ...]
     * - ADMIN_OVERVIEW: ['user' => UserInterface, 'statistics' => array, 'systemInformation' => array, ...]
     * - SERVER_DETAIL: ['user' => UserInterface, 'server' => ServerInterface, ...]
     *
     * @param WidgetContext $context Current rendering context
     * @param array $contextData Context-specific data (user, statistics, etc.)
     * @return array Template variables
     */
    public function getData(WidgetContext $context, array $contextData): array;

    /**
     * Determines if widget should be displayed for given context and data.
     * Can check roles, settings, feature flags, context type, etc.
     *
     * @param WidgetContext $context Current rendering context
     * @param array $contextData Context-specific data (user, statistics, etc.)
     * @return bool True to display, false to hide
     */
    public function isVisible(WidgetContext $context, array $contextData): bool;

    /**
     * Bootstrap column size (1-12) for responsive layout.
     * Default: 12 (full width within position).
     *
     * @return int Column size (1-12)
     */
    public function getColumnSize(): int;
}
