<?php

namespace App\Core\EventSubscriber;

use App\Core\Enum\WidgetPosition;
use App\Core\Service\Widget\WidgetRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Injects global widgets (navbar) into Twig for all requests.
 *
 * This subscriber runs on every request and makes navbar widgets available
 * globally in all Twig templates via the 'navbar_widgets' global variable.
 *
 * Navbar widgets are context-independent and appear across all pages.
 */
class GlobalWidgetSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WidgetRegistry $widgetRegistry,
        private readonly Environment $twig,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Only process main requests (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        // Get all navbar widgets (context-independent)
        $navbarWidgets = $this->widgetRegistry->getWidgetsByPositionOnly(WidgetPosition::NAVBAR);

        // Filter by visibility with current user context
        $user = $this->security->getUser();
        $contextData = ['user' => $user];

        $visibleWidgets = array_filter(
            $navbarWidgets,
            function ($widget) use ($contextData) {
                // Since navbar widgets support all contexts, we pass null for context
                // The widget's isVisible() should only check user-based conditions
                try {
                    // For navbar widgets, context doesn't matter, so we use first supported context
                    $supportedContexts = $widget->getSupportedContexts();
                    $context = !empty($supportedContexts) ? $supportedContexts[0] : null;

                    if ($context === null) {
                        return false;
                    }

                    return $widget->isVisible($context, $contextData);
                } catch (\Exception $e) {
                    // Hide widget if visibility check fails
                    return false;
                }
            }
        );

        // Inject into Twig globals
        $this->twig->addGlobal('navbar_widgets', array_values($visibleWidgets));
    }
}
