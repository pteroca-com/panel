<?php

namespace App\Core\Service\Plugin;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * Service for adding global notifications (flash messages) from plugins or core code.
 *
 * Flash messages are displayed once on the next page load and then automatically dismissed.
 */
readonly class PluginNotificationService
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    /**
     * Add a flash message that will be displayed on the next page load.
     *
     * @param string $type Type of notification: 'success', 'warning', 'danger', 'info'
     * @param string $message Message to display (can contain HTML)
     */
    public function addFlash(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    /**
     * Add a success notification.
     *
     * @param string $message Success message to display
     */
    public function success(string $message): void
    {
        $this->addFlash('success', $message);
    }

    /**
     * Add a warning notification.
     *
     * @param string $message Warning message to display
     */
    public function warning(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    /**
     * Add an error notification.
     *
     * @param string $message Error message to display
     */
    public function error(string $message): void
    {
        $this->addFlash('danger', $message);
    }

    /**
     * Add an informational notification.
     *
     * @param string $message Info message to display
     */
    public function info(string $message): void
    {
        $this->addFlash('info', $message);
    }
}
