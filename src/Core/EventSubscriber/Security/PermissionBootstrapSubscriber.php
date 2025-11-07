<?php

namespace App\Core\EventSubscriber\Security;

use App\Core\Event\Security\PermissionsRegisteredEvent;
use App\Core\Service\Security\PermissionRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Bootstrap subscriber that registers custom permissions early in the request lifecycle.
 *
 * Dispatches PermissionsRegisteredEvent on the first request to allow plugins to register
 * their custom permissions. Permissions must be registered before any security checks occur.
 *
 * The event is dispatched with very high priority (1024) to ensure permissions are available
 * before security voters are called.
 */
class PermissionBootstrapSubscriber implements EventSubscriberInterface
{
    private bool $permissionsInitialized = false;

    public function __construct(
        private readonly PermissionRegistry $permissionRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Very high priority to run before security checks
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
        ];
    }

    /**
     * Initialize permissions on first request.
     *
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only run on master request
        if (!$event->isMainRequest()) {
            return;
        }

        // Only initialize once per application lifecycle
        if ($this->permissionsInitialized) {
            return;
        }

        $this->initializePermissions();
    }

    /**
     * Initialize permission registry by dispatching PermissionsRegisteredEvent.
     *
     * @return void
     */
    private function initializePermissions(): void
    {
        $this->logger->debug('Initializing custom permissions');

        $startTime = microtime(true);

        // Dispatch event for plugins to register permissions
        $permissionsEvent = new PermissionsRegisteredEvent($this->permissionRegistry);
        $this->eventDispatcher->dispatch($permissionsEvent);

        $this->permissionsInitialized = true;

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $permissionCount = $this->permissionRegistry->count();

        $this->logger->info('Custom permissions initialized', [
            'permission_count' => $permissionCount,
            'duration_ms' => $duration,
        ]);

        if ($permissionCount > 0) {
            $this->logger->debug('Registered permissions by plugin', [
                'permissions' => $this->permissionRegistry->getPermissionsByPlugin(),
            ]);
        }
    }
}
