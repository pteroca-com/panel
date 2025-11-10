<?php

namespace App\Core\Event\Menu;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when collecting items for the settings menu dropdown.
 *
 * Plugins can subscribe to this event to add custom items to the global settings menu
 * (the gear icon in the navigation bar).
 */
class SettingsMenuCollectedEvent extends Event
{
    private array $menuItems = [];

    public function __construct(
        private readonly ?object $user = null
    ) {}

    /**
     * Add a menu item to the settings dropdown.
     *
     * @param string $type Type of menu item: 'link', 'divider', 'header'
     * @param string $label Label/text for the item (for header/link types)
     * @param string $url URL for link type (default: '#')
     * @param array $options Additional options:
     *   - icon: FontAwesome icon class (e.g., 'fas fa-cog')
     *   - data_attributes: Array of HTML data attributes (e.g., ['data-toggle' => 'modal'])
     *   - css_class: Additional CSS classes
     */
    public function addMenuItem(string $type, string $label, string $url = '#', array $options = []): void
    {
        $this->menuItems[] = [
            'type' => $type,
            'label' => $label,
            'url' => $url,
            'icon' => $options['icon'] ?? null,
            'data_attributes' => $options['data_attributes'] ?? [],
            'css_class' => $options['css_class'] ?? '',
        ];
    }

    /**
     * Get all registered menu items.
     *
     * @return array
     */
    public function getMenuItems(): array
    {
        return $this->menuItems;
    }

    /**
     * Get the current user (if authenticated).
     *
     * @return object|null
     */
    public function getUser(): ?object
    {
        return $this->user;
    }
}
