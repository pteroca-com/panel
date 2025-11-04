<?php

namespace App\Core\EventSubscriber\Console;

use App\Core\Service\Plugin\PluginCommandRegistry;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects Console Application into PluginCommandRegistry when console boots.
 *
 * This subscriber ensures that the PluginCommandRegistry has access to the
 * Console Application instance for registering plugin commands dynamically.
 */
readonly class PluginCommandRegistrySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginCommandRegistry $commandRegistry,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 2048],
        ];
    }

    /**
     * Inject the Application instance into PluginCommandRegistry.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $application = $event->getCommand()->getApplication();

        if ($application !== null) {
            $this->commandRegistry->setApplication($application);
        }
    }
}
