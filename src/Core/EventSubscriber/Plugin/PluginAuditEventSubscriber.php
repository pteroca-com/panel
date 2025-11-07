<?php

namespace App\Core\EventSubscriber\Plugin;

use App\Core\Event\Plugin\PluginDiscoveredEvent;
use App\Core\Event\Plugin\PluginDisabledEvent;
use App\Core\Event\Plugin\PluginEnabledEvent;
use App\Core\Event\Plugin\PluginFaultedEvent;
use App\Core\Event\Plugin\PluginRegisteredEvent;
use App\Core\Event\Plugin\PluginUpdatedEvent;
use App\Core\Service\Plugin\PluginAuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically logs plugin events to audit log.
 *
 * Subscribes to all plugin lifecycle events and logs them via PluginAuditLogger.
 * User context is extracted from Security when available (web requests),
 * otherwise logs as system action (CLI, background tasks).
 */
readonly class PluginAuditEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginAuditLogger $auditLogger,
        private Security          $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEnabledEvent::class => 'onPluginEnabled',
            PluginDisabledEvent::class => 'onPluginDisabled',
            PluginDiscoveredEvent::class => 'onPluginDiscovered',
            PluginRegisteredEvent::class => 'onPluginRegistered',
            PluginUpdatedEvent::class => 'onPluginUpdated',
            PluginFaultedEvent::class => 'onPluginFaulted',
        ];
    }

    public function onPluginEnabled(PluginEnabledEvent $event): void
    {
        $user = $this->security->getUser();

        $this->auditLogger->logPluginEnabled($event->getPlugin(), $user);
    }

    public function onPluginDisabled(PluginDisabledEvent $event): void
    {
        $user = $this->security->getUser();

        $this->auditLogger->logPluginDisabled($event->getPlugin(), $user);
    }

    public function onPluginDiscovered(PluginDiscoveredEvent $event): void
    {
        // System action - no user
        $this->auditLogger->logPluginDiscovered($event->getPluginPath(), $event->getManifest());
    }

    public function onPluginRegistered(PluginRegisteredEvent $event): void
    {
        // System action - no user
        $this->auditLogger->logPluginRegistered($event->getPlugin());
    }

    public function onPluginUpdated(PluginUpdatedEvent $event): void
    {
        $user = $this->security->getUser();

        $this->auditLogger->logPluginUpdated(
            $event->getPlugin(),
            $event->getOldVersion(),
            $user
        );
    }

    public function onPluginFaulted(PluginFaultedEvent $event): void
    {
        $user = $this->security->getUser();

        $this->auditLogger->logPluginFaulted(
            $event->getPlugin(),
            $event->getReason(),
            $user
        );
    }
}
